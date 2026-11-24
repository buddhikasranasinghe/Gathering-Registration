<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Identity\Entities\User;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantRelation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Participant>
 */
class ParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => null,
            'call_name' => null,
            'batch_id' => null,
            'phone' => null,
            'nic' => null,
            'attending' => true,
            'attendance_manually_set' => false,
            'primary_participant_id' => null,
            'relation' => null,
            'import_key' => null,
            'last_imported_at' => null,
            'archived_at' => null,
            'archive_note' => null,
            'archived_by' => null,
        ];
    }

    /**
     * Indicate that the participant was imported from a spreadsheet.
     */
    public function imported(): static
    {
        return $this->state(fn (): array => [
            'batch_id' => Batch::factory(),
            'phone' => '0773370555',
            'nic' => '933040186V',
            'import_key' => 'nic:933040186V',
            'last_imported_at' => now(),
        ]);
    }

    /**
     * Indicate that this participant is a relation attending with another participant.
     */
    public function relationTo(Participant $participant, ParticipantRelation $relation): static
    {
        return $this->state(fn (): array => [
            'primary_participant_id' => $participant->getKey(),
            'batch_id' => $participant->batch_id,
            'attending' => true,
            'relation' => $relation,
        ]);
    }

    /**
     * Indicate that the participant signed up in the application.
     */
    public function registered(): static
    {
        return $this->state(fn (): array => [
            'email' => fake()->unique()->safeEmail(),
            'import_key' => null,
        ]);
    }

    /**
     * Indicate that the participant was archived by an administrator.
     */
    public function archived(): static
    {
        return $this->state(fn (): array => [
            'archived_at' => now(),
            'archive_note' => 'Duplicate submission.',
            'archived_by' => User::factory(),
        ]);
    }
}
