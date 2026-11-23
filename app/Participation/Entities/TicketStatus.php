<?php

declare(strict_types=1);

namespace App\Participation\Entities;

enum TicketStatus: string
{
    case Issued = 'issued';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Issued => 'Issued',
            self::Cancelled => 'Cancelled',
        };
    }
}
