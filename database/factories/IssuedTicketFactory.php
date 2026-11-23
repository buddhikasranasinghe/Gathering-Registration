<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Identity\Entities\User;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Entities\TicketStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
            'ticket_number' => 'TCK-'.Str::upper(Str::random(6)),
            'issued_at' => now(),
            'status' => TicketStatus::Issued,
            'file_path' => null,
            'issued_by' => User::factory(),
        ];
    }
}
