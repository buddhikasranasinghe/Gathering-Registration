<?php

declare(strict_types=1);

namespace App\Participation\UseCases\Requests;

use App\Identity\Entities\User;
use App\Participation\Entities\Participant;

final readonly class IssueTicketRequest
{
    public function __construct(
        public Participant $participant,
        public User $issuedBy,
    ) {}
}
