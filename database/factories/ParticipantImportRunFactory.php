<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Participation\Entities\ImportStatus;
use App\Participation\Entities\ParticipantImportRun;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParticipantImportRun>
 */
class ParticipantImportRunFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requested_by' => null,
            'source_name' => 'participants.xlsx',
            'status' => ImportStatus::Running,
            'started_at' => now(),
            'finished_at' => null,
            'added_count' => 0,
            'updated_count' => 0,
            'unchanged_count' => 0,
            'skipped_count' => 0,
            'failure_reason' => null,
            'skipped_rows' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'status' => ImportStatus::Completed,
            'started_at' => now()->subMinute(),
            'finished_at' => now(),
        ]);
    }
}
