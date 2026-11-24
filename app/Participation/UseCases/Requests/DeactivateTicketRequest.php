<?php

declare(strict_types=1);

namespace App\Participation\UseCases\Requests;

use App\Identity\Entities\User;
use App\Participation\Entities\IssuedTicket;

final readonly class DeactivateTicketRequest
{
    public function __construct(
        public IssuedTicket $ticket,
        public User $deactivatedBy,
    ) {}
}
