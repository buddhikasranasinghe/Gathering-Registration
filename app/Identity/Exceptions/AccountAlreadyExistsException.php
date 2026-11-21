<?php

declare(strict_types=1);

namespace App\Identity\Exceptions;

use RuntimeException;

final class AccountAlreadyExistsException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('An account with this email already exists. Please sign in instead.');
    }
}
