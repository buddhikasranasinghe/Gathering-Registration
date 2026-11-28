<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantProgressStatus;
use App\Participation\Entities\ParticipantRelation;
use App\Participation\Entities\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;

final class ParticipantsPageTest extends TestCase
{
    #[Test]
    public function when_a_super_admin_views_the_page_then_the_roster_and_import_form_are_shown(): void
    {
        $participant = Participant::factory()->imported()->create(['name' => 'Chathuranga Ranasingh']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index'));

        $this->assertRosterShownToSuperAdmin($response, $participant);
    }

    #[Test]
    public function when_the_roster_is_shown_then_each_participant_links_to_the_details_page(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index'));

        $response->assertOk();
        $response->assertSee(route('participants.show', $participant), false);
        $response->assertSee('View details');
    }

    #[Test]
    public function when_the_attending_filter_is_applied_then_only_attending_participants_are_listed(): void
    {
        Participant::factory()->create(['name' => 'Attendee Alpha', 'attending' => true]);
        Participant::factory()->create(['name' => 'Absentee Beta', 'attending' => false]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['attendance' => 'attending']));

        $response->assertOk();
        $response->assertSee('Attendee Alpha');
        $response->assertDontSee('Absentee Beta');
    }

    #[Test]
    public function when_the_not_attending_filter_is_applied_then_only_those_participants_are_listed(): void
    {
        Participant::factory()->create(['name' => 'Attendee Alpha', 'attending' => true]);
        Participant::factory()->create(['name' => 'Absentee Beta', 'attending' => false]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['attendance' => 'not_attending']));

        $response->assertOk();
        $response->assertSee('Absentee Beta');
        $response->assertDontSee('Attendee Alpha');
        $response->assertSee('Not attending');
    }

    #[Test]
    public function when_the_roster_is_shown_then_relations_are_listed_in_the_relations_column(): void
    {
        $participant = Participant::factory()->create(['name' => 'Primary Person']);
        Participant::factory()->relationTo($participant, ParticipantRelation::Spouse)->create(['name' => 'Ruwani Perera']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index'));

        $response->assertOk();
        $response->assertSee('Relations');
        $response->assertSee('Ruwani Perera (spouse)', false);
        $response->assertDontSee('Spouse attending');
        $response->assertViewHas('stats', ['active' => 2, 'relations' => 1]);
    }

    #[Test]
    public function when_an_admin_views_the_page_then_the_import_form_is_hidden(): void
    {
        $response = $this->actingAs(User::factory()->admin()->create())
            ->get(route('participants.index'));

        $this->assertImportIsHiddenFromAdmin($response);
    }

    #[Test]
    public function when_an_admin_views_the_page_then_only_participants_from_its_batch_and_statistics_are_shown(): void
    {
        $batch = Batch::factory()->create();
        $admin = User::factory()->admin()->create(['batch_id' => $batch->id]);
        $member = Participant::factory()->for($batch)->create(['name' => 'Batch Member']);
        Participant::factory()->create(['name' => 'Other Batch Member']);

        $response = $this->actingAs($admin)->get(route('participants.index', ['batch_id' => 999999]));

        $response->assertOk();
        $response->assertSee($member->name);
        $response->assertDontSee('Other Batch Member');
        $response->assertViewHas('stats', ['active' => 1, 'relations' => 0]);
    }

    #[Test]
    public function when_a_main_treasurer_views_the_page_then_all_batches_can_be_filtered_and_import_is_hidden(): void
    {
        $batch = Batch::factory()->create(['name' => 'Forty']);
        Participant::factory()->for($batch)->create(['name' => 'Batch Member']);
        Participant::factory()->create(['name' => 'Other Batch Member']);

        $response = $this->actingAs(User::factory()->mainTreasurer()->create())
            ->get(route('participants.index', ['batch_id' => $batch->id]));

        $response->assertOk();
        $response->assertSee('Batch Member');
        $response->assertDontSee('Other Batch Member');
        $response->assertDontSee('Import spreadsheet');
        $response->assertViewHas('canFilterByBatch', true);
        $response->assertViewHas('canImport', false);
    }

    #[Test]
    public function when_an_admin_has_no_batch_then_the_roster_is_empty_and_the_reason_is_shown(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        Participant::factory()->create(['name' => 'Hidden Roster Member']);

        $response = $this->actingAs($admin)->get(route('participants.index'));

        $response->assertOk();
        $response->assertSee('No batch is assigned to your account. Contact the Super Admin.');
        $response->assertDontSee('Hidden Roster Member');
    }

    #[Test]
    public function when_a_participant_views_the_page_then_it_is_forbidden(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('participants.index'));

        $response->assertForbidden();
    }

    #[Test]
    public function when_a_guest_views_the_page_then_it_redirects_to_login(): void
    {
        $response = $this->get(route('participants.index'));

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function when_the_search_filter_is_applied_then_only_matching_participants_are_listed(): void
    {
        Participant::factory()->create(['name' => 'Alice Perera']);
        Participant::factory()->create(['name' => 'Bob Silva']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['search' => 'Alice']));

        $this->assertRosterIsFiltered($response);
    }

    #[Test]
    public function when_the_batch_filter_is_applied_then_only_participants_in_that_batch_are_listed(): void
    {
        $batch = Batch::factory()->create(['name' => 'Batch Thirty Seven']);
        Participant::factory()->for($batch)->create(['name' => 'Batch Member']);
        Participant::factory()->create(['name' => 'Different Batch Member']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['batch_id' => $batch->id]));

        $this->assertBatchRosterIsShown($response);
    }

    private function assertRosterShownToSuperAdmin(TestResponse $response, Participant $participant): void
    {
        $response->assertOk();
        $response->assertSee($participant->name);
        $response->assertSee('Import spreadsheet');
        $response->assertDontSee('Archived only');
        $response->assertDontSee('Archive participant');
        $response->assertViewHas('canImport', true);
    }

    private function assertImportIsHiddenFromAdmin(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertDontSee('Import spreadsheet');
        $response->assertDontSee('Spreadsheet import');
        $response->assertViewHas('canImport', false);
    }

    #[Test]
    public function when_no_payment_is_recorded_then_the_roster_shows_not_paid_status(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['name' => 'Unpaid Person']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index'));

        $response->assertOk();
        $response->assertViewHas(
            'progressStatuses',
            fn (array $statuses): bool => $statuses[$participant->getKey()] === ParticipantProgressStatus::NotPaid,
        );
        $response->assertSee('data-participant-status="not_paid"', false);
        $response->assertDontSee('Self-registered');
    }

    #[Test]
    public function when_a_partial_payment_is_recorded_then_the_roster_shows_payment_incomplete_status(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['name' => 'Partial Person']);
        Payment::factory()->for($participant)->create(['amount' => 1000]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index'));

        $response->assertOk();
        $response->assertViewHas(
            'progressStatuses',
            fn (array $statuses): bool => $statuses[$participant->getKey()] === ParticipantProgressStatus::PaymentIncomplete,
        );
        $response->assertSee('data-participant-status="payment_incomplete"', false);
        $response->assertSee('Payment Incomplete');
    }

    #[Test]
    public function when_the_full_amount_is_paid_then_the_roster_shows_payment_complete_status(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['name' => 'Paid Person']);
        Payment::factory()->for($participant)->create(['amount' => 4000]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index'));

        $response->assertOk();
        $response->assertViewHas(
            'progressStatuses',
            fn (array $statuses): bool => $statuses[$participant->getKey()] === ParticipantProgressStatus::PaymentComplete,
        );
        $response->assertSee('data-participant-status="payment_complete"', false);
        $response->assertSee('Payment Complete');
    }

    #[Test]
    public function when_an_active_ticket_exists_then_the_roster_shows_ticket_issued_status(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['name' => 'Ticketed Person']);
        Payment::factory()->for($participant)->create(['amount' => 4000]);
        IssuedTicket::factory()->for($participant)->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index'));

        $response->assertOk();
        $response->assertViewHas(
            'progressStatuses',
            fn (array $statuses): bool => $statuses[$participant->getKey()] === ParticipantProgressStatus::TicketIssued,
        );
        $response->assertSee('data-participant-status="ticket_issued"', false);
        $response->assertSee('Ticket Issued');
    }

    #[Test]
    public function the_roster_can_be_filtered_by_not_paid_status(): void
    {
        $this->seedParticipantsWithProgressStatuses();

        $this->assertStatusFilterReturns('not_paid', ParticipantProgressStatus::NotPaid, ['Status Not Paid']);
    }

    #[Test]
    public function the_roster_can_be_filtered_by_payment_incomplete_status(): void
    {
        $this->seedParticipantsWithProgressStatuses();

        $this->assertStatusFilterReturns('payment_incomplete', ParticipantProgressStatus::PaymentIncomplete, ['Status Incomplete']);
    }

    #[Test]
    public function the_roster_can_be_filtered_by_payment_complete_status(): void
    {
        $this->seedParticipantsWithProgressStatuses();

        $this->assertStatusFilterReturns('payment_complete', ParticipantProgressStatus::PaymentComplete, ['Status Complete', 'Status Deactivated']);
    }

    #[Test]
    public function the_roster_can_be_filtered_by_ticket_issued_status(): void
    {
        $this->seedParticipantsWithProgressStatuses();

        $this->assertStatusFilterReturns('ticket_issued', ParticipantProgressStatus::TicketIssued, ['Status Issued']);
    }

    #[Test]
    public function an_unknown_status_filter_value_is_ignored(): void
    {
        $this->seedParticipantsWithProgressStatuses();

        $this->assertStatusFilterReturns('unknown', null, [
            'Status Complete',
            'Status Deactivated',
            'Status Incomplete',
            'Status Issued',
            'Status Not Paid',
        ]);
    }

    #[Test]
    public function the_status_filter_uses_boolean_aware_attendance_comparisons(): void
    {
        config()->set('tickets.price_per_person', 4000);
        Participant::factory()->create(['name' => 'Status Not Paid']);

        DB::enableQueryLog();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['status' => 'payment_incomplete']))
            ->assertOk();

        $queries = collect(DB::getQueryLog())->pluck('query')->implode(' ');

        $this->assertStringContainsString('attending = TRUE', $queries);
        $this->assertStringNotContainsString('attending = 1', $queries);
    }

    #[Test]
    public function when_a_relation_exists_then_only_the_primary_participant_is_listed_in_the_roster(): void
    {
        $primary = Participant::factory()->create(['name' => 'Primary Person']);
        Participant::factory()->relationTo($primary, ParticipantRelation::Spouse)->create(['name' => 'Spouse Person']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index'));

        $response->assertOk();
        $response->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 1
                && $participants->getCollection()->pluck('name')->all() === ['Primary Person'],
        );
        $response->assertSee('Spouse Person (spouse)', false);
    }

    #[Test]
    public function when_the_household_has_paid_for_the_participant_and_relation_then_payment_complete_returns_the_primary(): void
    {
        config()->set('tickets.price_per_person', 2000);

        $primary = Participant::factory()->create(['name' => 'Couple Primary']);
        Participant::factory()->relationTo($primary, ParticipantRelation::Spouse)->create(['name' => 'Couple Spouse']);
        Payment::factory()->for($primary)->create(['amount' => 4000]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['status' => 'payment_complete']));

        $response->assertOk();
        $response->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 1
                && $participants->first()->is($primary),
        );
    }

    #[Test]
    public function when_the_household_has_partially_paid_then_payment_incomplete_returns_the_primary(): void
    {
        config()->set('tickets.price_per_person', 2000);

        $primary = Participant::factory()->create();
        Participant::factory()->relationTo($primary, ParticipantRelation::Spouse)->create();
        Payment::factory()->for($primary)->create(['amount' => 3000]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['status' => 'payment_incomplete']));

        $response->assertOk();
        $response->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 1
                && $participants->first()->is($primary),
        );
    }

    #[Test]
    public function when_a_relation_has_paid_then_the_household_is_payment_complete(): void
    {
        config()->set('tickets.price_per_person', 2000);

        $primary = Participant::factory()->create();
        $spouse = Participant::factory()->relationTo($primary, ParticipantRelation::Spouse)->create();
        Payment::factory()->for($primary)->create(['amount' => 2000]);
        Payment::factory()->for($spouse)->create(['amount' => 2000]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['status' => 'payment_complete']));

        $response->assertOk();
        $response->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 1
                && $participants->first()->is($primary),
        );
    }

    #[Test]
    public function when_the_search_matches_a_relation_then_the_primary_is_listed(): void
    {
        $primary = Participant::factory()->create(['name' => 'Primary Person']);
        Participant::factory()->relationTo($primary, ParticipantRelation::Spouse)->create(['name' => 'Ruwani Perera']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['search' => 'Ruwani']));

        $response->assertOk();
        $response->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 1
                && $participants->first()->is($primary),
        );
    }

    #[Test]
    public function when_a_relation_is_attending_then_the_household_matches_the_attending_filter_only(): void
    {
        $primary = Participant::factory()->create(['attending' => false]);
        Participant::factory()->relationTo($primary, ParticipantRelation::Spouse)->create(['attending' => true]);

        $attending = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['attendance' => 'attending']));

        $attending->assertOk();
        $attending->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 1
                && $participants->first()->is($primary),
        );

        $notAttending = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['attendance' => 'not_attending']));

        $notAttending->assertOk();
        $notAttending->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 0,
        );
    }

    /**
     * @param  list<string>  $expectedNames
     */
    private function assertStatusFilterReturns(string $value, ?ParticipantProgressStatus $expectedStatus, array $expectedNames): void
    {
        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.index', ['status' => $value]));

        $response->assertOk();
        $response->assertViewHas('filters.status', $expectedStatus);
        $response->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === count($expectedNames)
                && $participants->getCollection()->pluck('name')->all() === $expectedNames,
        );
    }

    private function seedParticipantsWithProgressStatuses(): void
    {
        config()->set('tickets.price_per_person', 4000);

        Participant::factory()->create(['name' => 'Status Not Paid']);

        $incomplete = Participant::factory()->create(['name' => 'Status Incomplete']);
        Payment::factory()->for($incomplete)->create(['amount' => 1000]);

        $complete = Participant::factory()->create(['name' => 'Status Complete']);
        Payment::factory()->for($complete)->create(['amount' => 4000]);

        $issued = Participant::factory()->create(['name' => 'Status Issued']);
        Payment::factory()->for($issued)->create(['amount' => 4000]);
        IssuedTicket::factory()->for($issued)->create();

        $deactivated = Participant::factory()->create(['name' => 'Status Deactivated']);
        Payment::factory()->for($deactivated)->create(['amount' => 4000]);
        IssuedTicket::factory()->for($deactivated)->deactivated()->create();
    }

    private function assertRosterIsFiltered(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 1
                && $participants->first()->name === 'Alice Perera',
        );
    }

    private function assertBatchRosterIsShown(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertViewHas(
            'participants',
            fn ($participants): bool => $participants->total() === 1
                && $participants->first()->name === 'Batch Member',
        );
    }
}
