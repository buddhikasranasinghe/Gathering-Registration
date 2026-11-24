<?php

declare(strict_types=1);

namespace App\Participation\Entities;

enum TicketStatus: string
{
    case Active = 'active';
    case Deactivated = 'deactivated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Deactivated => 'Deactivated',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }
}
