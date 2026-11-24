<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\AttendanceChange;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantRelation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class RelationManagementTest extends TestCase
{
    #[Test]
    public function when_a_spouse_is_added_then_an_attending_relation_is_recorded_and_audited(): void
    {
        $batch = Batch::factory()->create();
        $participant = Participant::factory()->for($batch)->create(['attending' => true]);
        $admin = User::factory()->superAdmin()->create(['name' => 'Buddhika']);

        $response = $this->actingAs($admin)->post(
            route('participants.relations.store', $participant),
            ['name' => 'Ruwani Perera', 'relation' => 'spouse'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Relation added.');

        $relation = $participant->refresh()->relatedParticipants()->sole();

        $this->assertSame('Ruwani Perera', $relation->name);
        $this->assertSame(ParticipantRelation::Spouse, $relation->relation);
        $this->assertTrue($relation->attending);
        $this->assertTrue($relation->attendance_manually_set);
        $this->assertSame($batch->getKey(), $relation->batch_id);
        $this->assertSame($participant->getKey(), $relation->primary_participant_id);

        $change = AttendanceChange::query()->sole();

        $this->assertSame($participant->getKey(), $change->participant_id);
        $this->assertSame($relation->getKey(), $change->relation_id);
        $this->assertSame(ParticipantRelation::Spouse, $change->relation_type);
        $this->assertSame(['Added Ruwani Perera (spouse) as Attending'], $change->changes());
        $this->assertSame($admin->getKey(), $change->changed_by);
    }

    #[Test]
    public function when_a_child_is_added_then_the_relation_is_labelled_as_a_child(): void
    {
        $participant = Participant::factory()->create();

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.relations.store', $participant),
            ['name' => 'Kasun Perera', 'relation' => 'child'],
        )->assertRedirect();

        $relation = $participant->refresh()->relatedParticipants()->sole();

        $this->assertSame(ParticipantRelation::Child, $relation->relation);
        $this->assertSame(['Added Kasun Perera (child) as Attending'], AttendanceChange::query()->sole()->changes());
    }

    #[Test]
    public function when_the_relation_payload_is_invalid_then_validation_errors_are_shown(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.relations.store', $participant),
            ['name' => '', 'relation' => 'friend'],
        );

        $response->assertRedirect();
        $response->assertSessionHasErrors(['name', 'relation']);
        $this->assertSame(0, $participant->refresh()->relatedParticipants()->count());
        $this->assertSame(0, AttendanceChange::query()->count());
    }

    #[Test]
    public function when_a_relation_is_marked_not_attending_then_the_price_drops_and_the_change_is_audited(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['attending' => true]);
        $relation = Participant::factory()->relationTo($participant, ParticipantRelation::Spouse)->create([
            'name' => 'Ruwani Perera',
            'attending' => true,
        ]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.relations.attendance.update', [$participant, $relation]),
            ['attending' => '0'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Attendance updated.');

        $this->assertFalse($relation->refresh()->attending);
        $this->assertTrue($relation->attendance_manually_set);
        $this->assertSame(1, $participant->attendingCount());
        $this->assertSame(
            ['Ruwani Perera: Attending → Not attending'],
            AttendanceChange::query()->sole()->changes(),
        );

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant))
            ->assertViewHas('summary', fn (array $summary): bool => $summary['ticketPrice'] === 4000.0);
    }

    #[Test]
    public function when_a_child_attends_then_only_the_participant_and_spouse_are_charged(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['attending' => true]);
        Participant::factory()->relationTo($participant, ParticipantRelation::Spouse)->create([
            'name' => 'Ruwani Perera',
            'attending' => true,
        ]);
        Participant::factory()->relationTo($participant, ParticipantRelation::Child)->create([
            'name' => 'Kasun Perera',
            'attending' => true,
        ]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertViewHas('summary', fn (array $summary): bool => $summary['ticketPrice'] === 8000.0
            && $summary['remaining'] === 8000.0
            && $summary['refund'] === 0.0);
        $response->assertViewHas('priceBreakdown', fn (array $lines): bool => $lines === [
            ['label' => 'Participant', 'quantity' => 1, 'unitPrice' => 4000.0, 'amount' => 4000.0],
            ['label' => 'Spouse', 'quantity' => 1, 'unitPrice' => 4000.0, 'amount' => 4000.0],
            ['label' => 'Child', 'quantity' => 1, 'unitPrice' => 0.0, 'amount' => 0.0],
        ]);
        $response->assertSee('Child 1', false);
        $response->assertSee('Free');
    }

    #[Test]
    public function when_a_relation_later_decides_to_attend_then_the_change_is_audited(): void
    {
        $participant = Participant::factory()->create();
        $relation = Participant::factory()->relationTo($participant, ParticipantRelation::Child)->create([
            'name' => 'Kasun Perera',
            'attending' => false,
        ]);

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.relations.attendance.update', [$participant, $relation]),
            ['attending' => '1'],
        )->assertRedirect();

        $this->assertTrue($relation->refresh()->attending);
        $this->assertSame(2, $participant->attendingCount());
        $this->assertSame(
            ['Kasun Perera: Not attending → Attending'],
            AttendanceChange::query()->sole()->changes(),
        );
    }

    #[Test]
    public function when_a_relation_is_removed_then_it_is_deleted_and_audited(): void
    {
        $participant = Participant::factory()->create();
        $relation = Participant::factory()->relationTo($participant, ParticipantRelation::Spouse)->create([
            'name' => 'Ruwani Perera',
        ]);

        $response = $this->actingAs(User::factory()->superAdmin()->create())->delete(
            route('participants.relations.destroy', [$participant, $relation]),
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Relation removed.');
        $this->assertNull($relation->fresh());
        $this->assertSame(1, $participant->attendingCount());
        $this->assertSame(['Removed Ruwani Perera (spouse)'], AttendanceChange::query()->sole()->changes());
    }

    #[Test]
    public function when_a_relation_belongs_to_another_participant_then_it_is_not_found(): void
    {
        $participant = Participant::factory()->create();
        $relation = Participant::factory()->relationTo(Participant::factory()->create(), ParticipantRelation::Spouse)->create();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(
            route('participants.relations.attendance.update', [$participant, $relation]),
            ['attending' => '0'],
        )->assertNotFound();

        $this->actingAs($admin)->delete(
            route('participants.relations.destroy', [$participant, $relation]),
        )->assertNotFound();

        $this->assertTrue($relation->refresh()->attending);
        $this->assertSame(0, AttendanceChange::query()->count());
    }

    #[Test]
    public function when_an_admin_adds_a_relation_outside_its_batch_then_it_is_not_found(): void
    {
        $admin = User::factory()->admin()->create(['batch_id' => Batch::factory()->create()]);
        $participant = Participant::factory()->for(Batch::factory())->create();

        $this->actingAs($admin)->post(
            route('participants.relations.store', $participant),
            ['name' => 'Ruwani Perera', 'relation' => 'spouse'],
        )->assertNotFound();

        $this->assertSame(0, $participant->refresh()->relatedParticipants()->count());
    }

    #[DataProvider('unprivilegedRoles')]
    #[Test]
    public function when_an_unprivileged_user_manages_relations_then_it_is_forbidden(UserRole $role): void
    {
        $participant = Participant::factory()->create();
        $user = $role === UserRole::Admin
            ? User::factory()->admin()->create(['batch_id' => $participant->batch_id])
            : User::factory()->create();

        $this->actingAs($user)->post(
            route('participants.relations.store', $participant),
            ['name' => 'Ruwani Perera', 'relation' => 'spouse'],
        )->assertForbidden();
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function unprivilegedRoles(): array
    {
        return [
            'participant' => [UserRole::User],
        ];
    }

    #[Test]
    public function when_the_details_page_is_opened_then_relations_and_the_add_form_are_shown(): void
    {
        $participant = Participant::factory()->create(['name' => 'Detail Person', 'call_name' => 'pini']);
        Participant::factory()->relationTo($participant, ParticipantRelation::Spouse)->create(['name' => 'Ruwani Perera']);

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('Detail Person (pini)', false);
        $response->assertSee('Ruwani Perera');
        $response->assertSee('(spouse)', false);
        $response->assertSee('Add a relation');
        $response->assertSee('Total ticket price');
        $response->assertSee('More information');
        $response->assertSee('Contact number');
        $response->assertDontSee('Save attendance');
    }
}
