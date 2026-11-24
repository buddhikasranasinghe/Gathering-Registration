<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Identity\Entities\User;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Entities\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<IssuedTicket>
 */
class IssuedTicketFactory extends Factory
{
    protected $model = IssuedTicket::class;

    public function definition(): array
    {
        return [
            'participant_id' => Participant::factory(),
            'ticket_number' => 'GATH-'.now()->year.'-'.fake()->unique()->numerify('#####'),
            'issued_at' => now(),
            'status' => TicketStatus::Active,
            'file_path' => null,
            'file_disk' => null,
            'ticket_price' => 4000,
            'attendees' => [['name' => fake()->name(), 'relation' => null]],
            'issued_by' => User::factory(),
            'issued_by_name' => fake()->name(),
            'deactivated_at' => null,
            'deactivated_by' => null,
            'deactivated_by_name' => null,
            'emailed_at' => null,
            'emailed_to' => null,
            'email_send_count' => 0,
        ];
    }

    /**
     * Indicate that the ticket has a generated file.
     */
    public function withFile(?string $path = null): static
    {
        return $this->state(fn (): array => [
            'file_path' => $path ?? '2026/GATH-'.now()->year.'-00001.pdf',
            'file_disk' => config('tickets.storage_disk'),
        ]);
    }

    /**
     * Indicate that the ticket has been deactivated.
     */
    public function deactivated(): static
    {
        return $this->state(fn (): array => [
            'status' => TicketStatus::Deactivated,
            'deactivated_at' => now(),
            'deactivated_by' => User::factory(),
            'deactivated_by_name' => fake()->name(),
        ]);
    }
}
