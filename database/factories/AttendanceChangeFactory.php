<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Identity\Entities\User;
use App\Participation\Entities\AttendanceChange;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantRelation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceChange>
 */
class AttendanceChangeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'participant_id' => Participant::factory(),
            'participant_from' => true,
            'participant_to' => true,
            'relation_id' => null,
            'relation_type' => ParticipantRelation::Spouse,
            'relation_name_from' => 'Ruwani Perera',
            'relation_name_to' => 'Ruwani Perera',
            'relation_from' => true,
            'relation_to' => false,
            'changed_by' => User::factory(),
        ];
    }
}
