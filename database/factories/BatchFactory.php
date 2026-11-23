<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Participation\Entities\Batch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Batch>
 */
class BatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Batch '.fake()->unique()->numberBetween(1, 99),
            'excel_mapper_name' => 'batch-'.fake()->unique()->numberBetween(1, 99),
        ];
    }
}
