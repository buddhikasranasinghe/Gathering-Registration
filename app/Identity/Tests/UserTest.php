<?php

declare(strict_types=1);

namespace App\Identity\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class UserTest extends TestCase
{
    #[DataProvider('roles')]
    #[Test]
    public function when_a_person_is_created_with_a_role_then_they_hold_that_role(UserRole $role): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->assertRoleAssigned($user, $role);
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function roles(): array
    {
        return collect(UserRole::cases())
            ->mapWithKeys(fn (UserRole $role): array => [$role->value => [$role]])
            ->all();
    }

    private function assertRoleAssigned(User $user, UserRole $role): void
    {
        $this->assertSame($role, $user->role);
        $this->assertSame([$role->value], $user->roles->pluck('name')->all());
    }
}
