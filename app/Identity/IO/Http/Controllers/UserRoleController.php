<?php

declare(strict_types=1);

namespace App\Identity\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Identity\Exceptions\UserRoleChangeNotAllowedException;
use App\Identity\IO\Http\Requests\UpdateUserRoleRequest;
use App\Identity\UseCases\Requests\UpdateUserRoleRequest as UpdateUserRole;
use App\Identity\UseCases\UpdateUserRoleUseCase;
use Illuminate\Http\RedirectResponse;

class UserRoleController extends Controller
{
    public function update(
        UpdateUserRoleRequest $request,
        User $user,
        UpdateUserRoleUseCase $updateUserRole,
    ): RedirectResponse {
        try {
            $updateUserRole->handle(new UpdateUserRole(
                requestedBy: $request->user(),
                user: $user,
                role: UserRole::from($request->string('role')->toString()),
            ));
        } catch (UserRoleChangeNotAllowedException $exception) {
            return back()->withErrors(['role' => $exception->getMessage()]);
        }

        return back()->with('status', $user->name.' is now an '.$user->role->label().'.');
    }
}
