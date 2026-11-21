<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the default super administrator account.
     */
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'buddhikasranasinghe96@gmail.com'],
            [
                'name' => 'Buddhika',
                'role' => UserRole::SuperAdmin,
                'email_verified_at' => now(),
            ],
        );

        $role = Role::findOrCreate(UserRole::SuperAdmin->value, config('auth.defaults.guard'));

        $user->syncRoles([$role]);
    }
}
