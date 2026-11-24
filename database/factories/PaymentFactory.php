<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Identity\Entities\User;
use App\Participation\Entities\Participant;
use App\Participation\Entities\Payment;
use App\Participation\Entities\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'participant_id' => Participant::factory(),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'method' => PaymentMethod::Cash,
            'paid_at' => now()->toDateString(),
            'slip_path' => null,
            'recorded_by' => User::factory(),
            'recorded_by_name' => fake()->name(),
            'updated_by' => null,
            'updated_by_name' => null,
        ];
    }
}
