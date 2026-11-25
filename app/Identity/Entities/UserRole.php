<?php

declare(strict_types=1);

namespace App\Identity\Entities;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case MainTreasurer = 'main_treasurer';
    case Admin = 'admin';
    case User = 'user';

    /**
     * Human readable name used in the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::MainTreasurer => 'Main Treasurer',
            self::Admin => 'Treasurer',
            self::User => 'User',
        };
    }
}
