<?php

declare(strict_types=1);

namespace App\Identity\UseCases\Requests;

use App\Participation\Entities\Participant;

final readonly class CreateUserFromParticipantRequest
{
    public function __construct(
        public Participant $participant,
        public string $email,
    ) {}
}
