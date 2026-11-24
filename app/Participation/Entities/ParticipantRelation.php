<?php

declare(strict_types=1);

namespace App\Participation\Entities;

enum ParticipantRelation: string
{
    case Spouse = 'spouse';
    case Child = 'child';

    public function label(): string
    {
        return match ($this) {
            self::Spouse => 'spouse',
            self::Child => 'child',
        };
    }
}
