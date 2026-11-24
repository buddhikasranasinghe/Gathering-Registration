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
        $role = UserRole::from($request->string('role')->toString());

        try {
            $updateUserRole->handle(new UpdateUserRole(
                requestedBy: $request->user(),
                user: $user,
                role: $role,
            ));
        } catch (UserRoleChangeNotAllowedException $exception) {
            return back()->withErrors(['role' => $exception->getMessage()]);
        }

        $roleWithArticle = match ($role) {
            UserRole::SuperAdmin => 'a Super Admin',
            UserRole::Admin => 'an Admin',
            UserRole::User => 'a User',
        };

        return back()->with('status', $user->name.' is now '.$roleWithArticle.'.');
    }
}
