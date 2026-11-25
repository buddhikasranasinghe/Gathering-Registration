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
            $requestedBy = $request->requestedBy->newQuery()->lockForUpdate()->findOrFail($request->requestedBy->getKey());
            $user = $request->user->newQuery()->lockForUpdate()->findOrFail($request->user->getKey());

            if (! $requestedBy->isSuperAdmin()) {
                throw new UserRoleChangeNotAllowedException('Only the Super Admin can change user roles.');
            }

            if ($requestedBy->is($user)) {
                throw new UserRoleChangeNotAllowedException('You cannot change your own role.');
            }

            if ($user->isSuperAdmin()
                && $request->role !== UserRole::SuperAdmin
                && $requestedBy->newQuery()->where('role', UserRole::SuperAdmin)->count() <= 1
            ) {
                throw new UserRoleChangeNotAllowedException('At least one Super Admin must remain.');
            }

            if ($request->role === UserRole::Admin && $user->batch_id === null) {
                throw new UserRoleChangeNotAllowedException('A user without a batch cannot be promoted to Treasurer. Update the participant spreadsheet first.');
            }

            $user->forceFill(['role' => $request->role])->save();
        });
    }
}
