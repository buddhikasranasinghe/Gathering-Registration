<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Participation\Entities\AttendanceChange;
use App\Participation\Entities\Batch;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantProgressStatus;
use App\Participation\Entities\ParticipantRelation;
use App\Participation\Entities\Payment;
use PHPUnit\Framework\Attributes\Test;

final class ParticipantShowTest extends TestCase
{
    #[Test]
    public function when_a_super_admin_views_a_participant_then_the_details_page_is_shown(): void
    {
        $participant = Participant::factory()->create(['name' => 'Detail Person']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Detail Person');
        $response->assertSee('Payment history');
        $response->assertSee('Issued tickets');
        $response->assertSee('Total ticket price');
        $response->assertSee('More information');
        $response->assertSee('data-history-open="attendance-history"', false);
        $response->assertSee('name="attending" value="0"', false);
    }

    #[Test]
    public function when_a_main_treasurer_views_a_participant_from_another_batch_then_the_details_page_is_shown(): void
    {
        $participant = Participant::factory()->create([
            'name' => 'Global Person',
            'batch_id' => Batch::factory(),
        ]);

        $response = $this->actingAs(User::factory()->mainTreasurer()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Global Person');
        $response->assertViewHas('canManage', true);
        $response->assertViewHas('canCreateUserAccount', false);
    }

    #[Test]
    public function when_attendance_was_changed_then_the_change_history_popup_shows_the_action_actor_and_time(): void
    {
        $this->travelTo('2026-11-20 18:45:00');

        $participant = Participant::factory()->create();
        $admin = User::factory()->superAdmin()->create(['name' => 'Buddhika']);
        AttendanceChange::factory()->for($participant)->create([
            'changed_by' => $admin->getKey(),
            'participant_from' => true,
            'participant_to' => true,
            'relation_type' => ParticipantRelation::Spouse,
            'relation_name_from' => 'Ruwani Perera',
            'relation_name_to' => 'Ruwani Perera',
            'relation_from' => true,
            'relation_to' => false,
        ]);

        $response = $this->actingAs($admin)->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('History');
        $response->assertSee('Buddhika');
        $response->assertSee('Ruwani Perera: Attending → Not attending');
        $response->assertSee('20 Nov 2026, 18:45');
        $response->assertSee('Close');
    }

    #[Test]
    public function when_the_details_page_is_opened_then_the_three_tabs_are_shown(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Participant Details');
        $response->assertSee('Payment Details');
        $response->assertSee('Ticket details');
        $response->assertDontSee('Archive participant');
        $response->assertViewHas('activeTab', 'participant');
    }

    #[Test]
    public function when_a_tab_is_requested_then_it_is_selected(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'payment']));

        $response->assertOk();
        $response->assertViewHas('activeTab', 'payment');
        $response->assertSee('aria-controls="panel-payment"', false);
        $response->assertSee('Record a payment');
    }

    #[Test]
    public function when_an_unknown_tab_is_requested_then_the_participant_tab_is_selected(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'unknown']));

        $response->assertOk();
        $response->assertViewHas('activeTab', 'participant');
    }

    #[Test]
    public function when_a_payment_is_listed_then_its_row_opens_the_edit_popup_with_details_and_slip_tabs(): void
    {
        $participant = Participant::factory()->create();
        Payment::factory()->for($participant)->create(['slip_path' => 'payment-slips/receipt.jpg']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'payment']));

        $response->assertOk();
        $response->assertSee('aria-label="Edit payment of', false);
        $response->assertSee('data-payment-form-tab="details"', false);
        $response->assertSee('data-payment-form-tab="slip"', false);
        $response->assertSee('data-payment-slip-change', false);
        $response->assertSee('data-payment-slip-remove', false);
        $response->assertSee('data-payment-slip-kind="image"', false);
        $response->assertSee('data-payment-slip-pdf', false);
        $response->assertSee('data-payment-slip-pdf-url', false);
        $response->assertSee('accept=".pdf,.jpg,.jpeg,.png,.heic,.heif"', false);
    }

    #[Test]
    public function when_an_admin_views_a_participant_in_its_batch_then_the_details_page_is_shown(): void
    {
        $batch = Batch::factory()->create();
        $admin = User::factory()->admin()->create(['batch_id' => $batch->id]);
        $participant = Participant::factory()->for($batch)->create(['name' => 'Batch Person']);

        $response = $this->actingAs($admin)->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Batch Person');
    }

    #[Test]
    public function when_an_admin_views_a_participant_in_another_batch_then_it_is_not_found(): void
    {
        $adminBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $admin = User::factory()->admin()->create(['batch_id' => $adminBatch->id]);
        $participant = Participant::factory()->for($otherBatch)->create();

        $response = $this->actingAs($admin)->get(route('participants.show', $participant));

        $response->assertNotFound();
    }

    #[Test]
    public function when_a_participant_views_the_details_page_then_it_is_forbidden(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->create())
            ->get(route('participants.show', $participant));

        $response->assertForbidden();
    }

    #[Test]
    public function when_a_guest_views_the_details_page_then_it_redirects_to_login(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->get(route('participants.show', $participant));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function when_no_payment_is_recorded_then_the_progress_bar_shows_application_received(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertViewHas('progressStatus', ParticipantProgressStatus::NotPaid);
        $response->assertSee('data-participant-progress', false);
        $response->assertSee('data-current-step="1"', false);
        $response->assertSee('Application Received');
        $response->assertSee('data-progress-step="not_paid" data-progress-state="complete"', false);
        $response->assertSee('data-progress-step="payment_complete" data-progress-state="current"', false);
        $response->assertDontSee('data-progress-step="payment_incomplete"', false);
        $response->assertSee('border-slate-300 bg-white/60 text-slate-500', false);
        $this->assertSame(1, substr_count($response->getContent(), 'border-emerald-500 bg-emerald-500 text-white'));
        $response->assertDontSee('bg-violet-600', false);
    }

    #[Test]
    public function when_a_partial_payment_is_recorded_then_the_progress_bar_still_shows_application_received(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create();
        Payment::factory()->for($participant)->create(['amount' => 1000]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertViewHas('progressStatus', ParticipantProgressStatus::PaymentIncomplete);
        $response->assertSee('data-current-step="1"', false);
        $response->assertSee('Application Received');
        $response->assertSee('data-progress-step="not_paid" data-progress-state="complete"', false);
        $response->assertSee('data-progress-step="payment_complete" data-progress-state="current"', false);
        $response->assertDontSee('data-progress-step="payment_incomplete"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'border-emerald-500 bg-emerald-500 text-white'));
    }

    #[Test]
    public function when_the_full_amount_is_paid_then_the_progress_bar_shows_payment_complete(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create();
        Payment::factory()->for($participant)->create(['amount' => 4000]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertViewHas('progressStatus', ParticipantProgressStatus::PaymentComplete);
        $response->assertSee('data-current-step="2"', false);
        $response->assertSee('data-progress-step="not_paid" data-progress-state="complete"', false);
        $response->assertSee('data-progress-step="payment_complete" data-progress-state="complete"', false);
        $response->assertSee('data-progress-step="ticket_issued" data-progress-state="current"', false);
        $this->assertSame(2, substr_count($response->getContent(), 'border-emerald-500 bg-emerald-500 text-white'));
    }

    #[Test]
    public function when_an_active_ticket_exists_then_the_progress_bar_shows_ticket_issued(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create();
        Payment::factory()->for($participant)->create(['amount' => 4000]);
        IssuedTicket::factory()->for($participant)->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertViewHas('progressStatus', ParticipantProgressStatus::TicketIssued);
        $response->assertSee('data-current-step="3"', false);
        $response->assertSee('data-progress-step="not_paid" data-progress-state="complete"', false);
        $response->assertSee('data-progress-step="payment_complete" data-progress-state="complete"', false);
        $response->assertSee('data-progress-step="ticket_issued" data-progress-state="complete"', false);
        $response->assertDontSee('data-progress-state="current"', false);
        $response->assertDontSee('border-slate-300 bg-white/60 text-slate-500', false);
        $this->assertSame(3, substr_count($response->getContent(), 'border-emerald-500 bg-emerald-500 text-white'));
    }

    #[Test]
    public function when_only_a_deactivated_ticket_exists_then_the_progress_bar_shows_payment_complete(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create();
        Payment::factory()->for($participant)->create(['amount' => 4000]);
        IssuedTicket::factory()->for($participant)->deactivated()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertViewHas('progressStatus', ParticipantProgressStatus::PaymentComplete);
        $response->assertSee('data-current-step="2"', false);
        $response->assertSee('data-progress-step="payment_complete" data-progress-state="complete"', false);
        $response->assertSee('data-progress-step="ticket_issued" data-progress-state="current"', false);
    }

    #[Test]
    public function when_the_details_page_is_opened_then_header_status_badges_are_not_shown(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertDontSee('Self-registered');
        $response->assertSee('Attendance');
    }

    #[Test]
    public function when_a_relation_has_paid_then_the_payment_summary_includes_it(): void
    {
        config()->set('tickets.price_per_person', 2000);

        $primary = Participant::factory()->create();
        $spouse = Participant::factory()->relationTo($primary, ParticipantRelation::Spouse)->create();
        Payment::factory()->for($primary)->create(['amount' => 2000]);
        Payment::factory()->for($spouse)->create(['amount' => 2000]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $primary));

        $response->assertOk();
        $response->assertViewHas(
            'summary',
            fn (array $summary): bool => $summary['ticketPrice'] === 4000.0
                && $summary['totalPaid'] === 4000.0
                && $summary['remaining'] === 0.0,
        );
        $response->assertViewHas('progressStatus', ParticipantProgressStatus::PaymentComplete);
    }
}
