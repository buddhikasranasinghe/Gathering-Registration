<?php

declare(strict_types=1);

namespace App\Identity\UseCases;

use App\Identity\Entities\UserRole;
use App\Identity\Exceptions\UserRoleChangeNotAllowedException;
use App\Identity\UseCases\Requests\UpdateUserRoleRequest;

final class UpdateUserRoleUseCase
{
    /**
     * @throws UserRoleChangeNotAllowedException
     */
    public function handle(UpdateUserRoleRequest $request): void
    {
        if (! $request->requestedBy->isSuperAdmin()) {
            throw new UserRoleChangeNotAllowedException('Only the Super Admin can change user roles.');
        }

        if ($request->user->isSuperAdmin()) {
            throw new UserRoleChangeNotAllowedException('The Super Admin role is protected.');
        }

        if (! in_array($request->role, [UserRole::User, UserRole::Admin], true)) {
            throw new UserRoleChangeNotAllowedException('The Super Admin role cannot be assigned.');
        }

        $request->user->forceFill(['role' => $request->role])->save();
    }
}
