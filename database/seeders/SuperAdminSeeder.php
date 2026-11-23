<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    /**
     * Seed the default super administrator account.
     */
    public function run(): void
    {
        $batch = Batch::query()->where('name', '40')->first();

        $user = User::query()->updateOrCreate(
            ['email' => 'buddhikasranasinghe96@gmail.com'],
            [
                'name' => 'Buddhika',
                'role' => UserRole::SuperAdmin,
                'batch_id' => $batch?->getKey(),
                'email_verified_at' => now(),
            ],
        );

        $role = Role::findOrCreate(UserRole::SuperAdmin->value, config('auth.defaults.guard'));

        $user->syncRoles([$role]);
    }
}
