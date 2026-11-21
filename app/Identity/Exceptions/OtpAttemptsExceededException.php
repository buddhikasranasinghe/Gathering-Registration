<?php

declare(strict_types=1);

namespace App\Identity\Exceptions;

final class OtpAttemptsExceededException extends OtpException
{
    public function __construct()
    {
        parent::__construct('Too many incorrect attempts. Please request a new code.');
    }
}
