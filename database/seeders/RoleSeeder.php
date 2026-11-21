<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Identity\Entities\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Create the application roles used by Spatie's permission package.
     */
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            Role::findOrCreate($role->value, config('auth.defaults.guard'));
        }
    }
}
