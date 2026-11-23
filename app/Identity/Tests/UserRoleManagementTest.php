<?php

declare(strict_types=1);

namespace App\Identity\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class UserRoleManagementTest extends TestCase
{
    #[DataProvider('roleTransitions')]
    #[Test]
    public function when_a_super_admin_changes_a_user_role_then_the_new_role_is_saved_and_synced(
        UserRole $currentRole,
        UserRole $newRole,
    ): void {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->for(Batch::factory())->create(['role' => $currentRole]);

        $response = $this->actingAs($superAdmin)->patch(
            route('users.role.update', $user),
            ['role' => $newRole->value],
        );

        $this->assertRoleWasUpdated($response, $user, $newRole);
    }

    #[Test]
    public function when_a_super_admin_promotes_a_user_without_a_batch_then_the_change_is_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($superAdmin)->patch(
            route('users.role.update', $user),
            ['role' => UserRole::Admin->value],
        );

        $response->assertSessionHasErrors([
            'role' => 'A user without a batch cannot be promoted to Admin. Update the participant spreadsheet first.',
        ]);
        $this->assertSame(UserRole::User, $user->refresh()->role);
    }

    #[Test]
    public function when_a_super_admin_attempts_to_assign_the_super_admin_role_then_the_change_is_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($superAdmin)->patch(
            route('users.role.update', $user),
            ['role' => UserRole::SuperAdmin->value],
        );

        $this->assertSuperAdminRoleWasRejected($response, $user);
    }

    #[Test]
    public function when_a_super_admin_attempts_to_change_the_protected_role_then_the_change_is_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($superAdmin)->patch(
            route('users.role.update', $superAdmin),
            ['role' => UserRole::User->value],
        );

        $this->assertProtectedRoleWasNotChanged($response, $superAdmin);
    }

    #[DataProvider('nonSuperAdminRoles')]
    #[Test]
    public function when_a_non_super_admin_changes_a_user_role_then_the_request_is_forbidden(UserRole $role): void
    {
        $actor = $role === UserRole::Admin
            ? User::factory()->admin()->create()
            : User::factory()->create();
        $user = User::factory()->create();

        $response = $this->actingAs($actor)->patch(
            route('users.role.update', $user),
            ['role' => UserRole::Admin->value],
        );

        $this->assertRoleChangeWasForbidden($response, $user);
    }

    #[Test]
    public function when_a_guest_changes_a_user_role_then_the_request_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $response = $this->patch(
            route('users.role.update', $user),
            ['role' => UserRole::Admin->value],
        );

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function when_a_super_admin_views_the_users_page_then_the_protected_and_editable_roles_are_shown(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['name' => 'Buddhika']);
        $user = User::factory()->for(Batch::factory()->state(['name' => '40']))->create(['name' => 'Saman']);

        $response = $this->actingAs($superAdmin)->get(route('users.index'));

        $this->assertUsersPageIsShown($response, $user);
    }

    #[Test]
    public function when_the_users_search_is_applied_then_only_matching_users_are_listed(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        User::factory()->create(['name' => 'Alice Perera']);
        User::factory()->create(['name' => 'Bob Silva']);

        $response = $this->actingAs($superAdmin)->get(route('users.index', ['search' => 'Alice']));

        $this->assertUsersPageIsFiltered($response);
    }

    #[DataProvider('nonSuperAdminRoles')]
    #[Test]
    public function when_a_non_super_admin_views_the_users_page_then_the_request_is_forbidden(UserRole $role): void
    {
        $user = $role === UserRole::Admin
            ? User::factory()->admin()->create()
            : User::factory()->create();

        $response = $this->actingAs($user)->get(route('users.index'));

        $response->assertForbidden();
    }

    /**
     * @return array<string, array{UserRole, UserRole}>
     */
    public static function roleTransitions(): array
    {
        return [
            'promote a user' => [UserRole::User, UserRole::Admin],
            'demote an admin' => [UserRole::Admin, UserRole::User],
        ];
    }

    /**
     * @return array<string, array{UserRole}>
     */
    public static function nonSuperAdminRoles(): array
    {
        return [
            'admin' => [UserRole::Admin],
            'user' => [UserRole::User],
        ];
    }

    private function assertRoleWasUpdated(TestResponse $response, User $user, UserRole $role): void
    {
        $response->assertRedirect();
        $user->refresh();

        $this->assertSame($role, $user->role);
        $this->assertSame([$role->value], $user->roles()->pluck('name')->all());
    }

    private function assertSuperAdminRoleWasRejected(TestResponse $response, User $user): void
    {
        $response->assertSessionHasErrors('role');
        $user->refresh();

        $this->assertSame(UserRole::User, $user->role);
    }

    private function assertProtectedRoleWasNotChanged(TestResponse $response, User $superAdmin): void
    {
        $response->assertSessionHasErrors(['role' => 'The Super Admin role is protected.']);
        $superAdmin->refresh();

        $this->assertSame(UserRole::SuperAdmin, $superAdmin->role);
    }

    private function assertRoleChangeWasForbidden(TestResponse $response, User $user): void
    {
        $response->assertForbidden();
        $user->refresh();

        $this->assertSame(UserRole::User, $user->role);
    }

    private function assertUsersPageIsShown(TestResponse $response, User $user): void
    {
        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee('Protected');
        $response->assertSee('40');
        $response->assertSee('Update role');
    }

    private function assertUsersPageIsFiltered(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertViewHas(
            'users',
            fn ($users): bool => $users->total() === 1
                && $users->first()->name === 'Alice Perera',
        );
    }
}
