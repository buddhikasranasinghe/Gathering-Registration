<?php

declare(strict_types=1);

namespace App\Identity\Observers;

use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use Spatie\Permission\Models\Role;

final class UserObserver
{
    public function saved(User $user): void
    {
        if (! $this->shouldSyncRole($user)) {
            return;
        }

        $this->syncRole($user);
    }

    private function shouldSyncRole(User $user): bool
    {
        return $user->wasRecentlyCreated || $user->wasChanged('role');
    }

    /**
     * Keep the Spatie role in sync with the canonical role column so that
     * granular permissions can be attached to roles later.
     */
    private function syncRole(User $user): void
    {
        $role = $user->role ?? UserRole::User;

        $user->syncRoles([
            Role::findOrCreate($role->value, config('auth.defaults.guard')),
        ]);
    }
}
