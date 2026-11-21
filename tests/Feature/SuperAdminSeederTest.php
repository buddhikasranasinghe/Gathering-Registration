<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use Database\Seeders\SuperAdminSeeder;
use PHPUnit\Framework\Attributes\Test;

class SuperAdminSeederTest extends TestCase
{
    private const SUPER_ADMIN_EMAIL = 'buddhikasranasinghe96@gmail.com';

    #[Test]
    public function when_the_super_admin_is_seeded_then_the_default_account_is_ready(): void
    {
        $this->seed(SuperAdminSeeder::class);

        $user = User::query()->where('email', self::SUPER_ADMIN_EMAIL)->sole();

        $this->assertDefaultSuperAdminReady($user);
    }

    #[Test]
    public function when_the_super_admin_is_seeded_twice_then_only_one_account_exists(): void
    {
        $this->seed(SuperAdminSeeder::class);
        $this->seed(SuperAdminSeeder::class);

        $this->assertSame(
            1,
            User::query()->where('email', self::SUPER_ADMIN_EMAIL)->count(),
        );
    }

    private function assertDefaultSuperAdminReady(User $user): void
    {
        $this->assertSame('Buddhika', $user->name);
        $this->assertSame(UserRole::SuperAdmin, $user->role);
        $this->assertSame(
            [UserRole::SuperAdmin->value],
            $user->roles->pluck('name')->all(),
        );
        $this->assertNull($user->password);
        $this->assertNotNull($user->email_verified_at);
    }
}
