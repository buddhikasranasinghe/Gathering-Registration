<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Participation\Entities\AttendanceChange;
use App\Participation\Entities\Batch;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantRelation;
use App\Participation\Entities\Payment;
use PHPUnit\Framework\Attributes\Test;

final class AttendanceUpdateTest extends TestCase
{
    #[Test]
    public function when_a_participant_is_marked_not_attending_then_the_decision_and_audit_are_saved(): void
    {
        $participant = Participant::factory()->create([
            'name' => 'Attendance Person',
            'attending' => true,
        ]);
        $admin = User::factory()->superAdmin()->create(['name' => 'Buddhika']);

        $response = $this->actingAs($admin)->post(
            route('participants.attendance.update', $participant),
            ['attending' => '0'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Attendance updated.');

        $participant->refresh();

        $this->assertFalse($participant->attending);
        $this->assertTrue($participant->attendance_manually_set);

        $change = AttendanceChange::query()->sole();

        $this->assertSame(true, $change->participant_from);
        $this->assertSame(false, $change->participant_to);
        $this->assertSame($admin->getKey(), $change->changed_by);
        $this->assertSame(['Participant: Attending → Not attending'], $change->changes());
    }

    #[Test]
    public function when_a_participant_later_decides_to_attend_then_the_decision_and_audit_are_saved(): void
    {
        $participant = Participant::factory()->create(['attending' => false]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.attendance.update', $participant),
            ['attending' => '1'],
        );

        $response->assertRedirect();
        $this->assertTrue($participant->refresh()->attending);

        $change = AttendanceChange::query()->sole();

        $this->assertSame(false, $change->participant_from);
        $this->assertSame(true, $change->participant_to);
        $this->assertSame(['Participant: Not attending → Attending'], $change->changes());
    }

    #[Test]
    public function when_attendance_changes_then_the_ticket_price_and_refund_are_recalculated(): void
    {
        config()->set('tickets.price_per_person', 25000);

        $participant = Participant::factory()->create(['attending' => true]);
        Participant::factory()->relationTo($participant, ParticipantRelation::Spouse)->create([
            'name' => 'Ruwani Perera',
            'attending' => true,
        ]);
        Payment::factory()->for($participant)->create(['amount' => 50000]);

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.attendance.update', $participant),
            ['attending' => '0'],
        )->assertRedirect();

        $response = $this->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertViewHas('summary', fn (array $summary): bool => $summary['ticketPrice'] === 25000.0
            && $summary['totalPaid'] === 50000.0
            && $summary['remaining'] === 0.0
            && $summary['refund'] === 25000.0);
    }

    #[Test]
    public function when_nothing_changes_then_no_audit_record_is_created(): void
    {
        $participant = Participant::factory()->create(['attending' => true]);

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.attendance.update', $participant),
            ['attending' => '1'],
        )->assertRedirect();

        $this->assertTrue($participant->refresh()->attendance_manually_set);
        $this->assertSame(0, AttendanceChange::query()->count());
    }

    #[Test]
    public function when_an_admin_updates_a_participant_in_its_batch_then_it_is_allowed(): void
    {
        $batch = Batch::factory()->create();
        $admin = User::factory()->admin()->create(['batch_id' => $batch->id]);
        $participant = Participant::factory()->for($batch)->create(['attending' => true]);

        $response = $this->actingAs($admin)->post(
            route('participants.attendance.update', $participant),
            ['attending' => '0'],
        );

        $response->assertRedirect();
        $this->assertFalse($participant->refresh()->attending);
    }

    #[Test]
    public function when_an_admin_updates_a_participant_in_another_batch_then_it_is_not_found(): void
    {
        $adminBatch = Batch::factory()->create();
        $otherBatch = Batch::factory()->create();
        $admin = User::factory()->admin()->create(['batch_id' => $adminBatch->id]);
        $participant = Participant::factory()->for($otherBatch)->create(['attending' => true]);

        $response = $this->actingAs($admin)->post(
            route('participants.attendance.update', $participant),
            ['attending' => '0'],
        );

        $response->assertNotFound();
        $this->assertTrue($participant->refresh()->attending);
        $this->assertSame(0, AttendanceChange::query()->count());
    }

    #[Test]
    public function when_a_participant_updates_attendance_then_it_is_forbidden(): void
    {
        $participant = Participant::factory()->create(['attending' => true]);

        $response = $this->actingAs(User::factory()->create())->post(
            route('participants.attendance.update', $participant),
            ['attending' => '0'],
        );

        $response->assertForbidden();
        $this->assertTrue($participant->refresh()->attending);
    }

    #[Test]
    public function when_a_guest_updates_attendance_then_it_redirects_to_login(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->post(
            route('participants.attendance.update', $participant),
            ['attending' => '0'],
        );

        $response->assertRedirect(route('login'));
        $this->assertSame(0, AttendanceChange::query()->count());
    }

    #[Test]
    public function when_the_attendance_payload_is_empty_then_validation_errors_are_shown(): void
    {
        $participant = Participant::factory()->create(['attending' => true]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.attendance.update', $participant),
            [],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors(['attending']);

        $this->assertTrue($participant->refresh()->attending);
        $this->assertSame(0, AttendanceChange::query()->count());
    }

    #[Test]
    public function when_a_ticket_was_issued_then_the_details_page_warns_about_not_attending(): void
    {
        $participant = Participant::factory()->create(['attending' => false]);
        IssuedTicket::factory()->for($participant)->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('A ticket has already been issued while someone is not attending.');
    }
}
