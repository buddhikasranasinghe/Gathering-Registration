<?php

declare(strict_types=1);

namespace App\Identity\UseCases;

use App\Identity\Entities\UserRole;
use App\Identity\Exceptions\UserRoleChangeNotAllowedException;
use App\Identity\UseCases\Requests\UpdateUserRoleRequest;
use Illuminate\Support\Facades\DB;

final class UpdateUserRoleUseCase
{
    /**
     * @throws UserRoleChangeNotAllowedException
     */
    public function handle(UpdateUserRoleRequest $request): void
    {
        DB::transaction(function () use ($request): void {
            $user = $request->user->newQuery()->lockForUpdate()->findOrFail($request->user->getKey());

            if (! $request->requestedBy->isSuperAdmin()) {
                throw new UserRoleChangeNotAllowedException('Only the Super Admin can change user roles.');
            }

            if ($user->isSuperAdmin()) {
                throw new UserRoleChangeNotAllowedException('The Super Admin role is protected.');
            }

            if (! in_array($request->role, [UserRole::User, UserRole::Admin], true)) {
                throw new UserRoleChangeNotAllowedException('The Super Admin role cannot be assigned.');
            }

            if ($request->role === UserRole::Admin && $user->batch_id === null) {
                throw new UserRoleChangeNotAllowedException('A user without a batch cannot be promoted to Admin. Update the participant spreadsheet first.');
            }

            $user->forceFill(['role' => $request->role])->save();
        });
    }
}
