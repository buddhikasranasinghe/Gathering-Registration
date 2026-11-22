<?php

declare(strict_types=1);

namespace App\Identity\UseCases\Requests;

use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;

final readonly class UpdateUserRoleRequest
{
    public function __construct(
        public User $requestedBy,
        public User $user,
        public UserRole $role,
    ) {}
}
