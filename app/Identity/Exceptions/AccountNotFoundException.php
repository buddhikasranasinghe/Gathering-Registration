<?php

declare(strict_types=1);

namespace App\Identity\Exceptions;

use RuntimeException;

final class AccountNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('We could not find an account for this email address.');
    }
}
