<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Participation\Entities\Batch;
use Illuminate\Database\Seeder;

class BatchSeeder extends Seeder
{
    /**
     * Seed the batches represented in the participant spreadsheets.
     */
    public function run(): void
    {
        foreach (range(36, 40) as $number) {
            $year = $number + 1976;

            Batch::query()->firstOrCreate(
                ['name' => (string) $number],
                ['excel_mapper_name' => sprintf('%d (%d - %d)', $number, $year, $year + 1)],
            );
        }
    }
}
