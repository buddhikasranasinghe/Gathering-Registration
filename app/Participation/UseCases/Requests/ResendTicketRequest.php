<?php

declare(strict_types=1);

namespace App\Participation\UseCases\Requests;

use App\Participation\Entities\IssuedTicket;

final readonly class ResendTicketRequest
{
    public function __construct(
        public IssuedTicket $ticket,
        public string $recipient,
    ) {}
}
