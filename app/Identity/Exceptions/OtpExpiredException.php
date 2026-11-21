<?php

declare(strict_types=1);

namespace App\Identity\Exceptions;

final class OtpExpiredException extends OtpException
{
    public function __construct()
    {
        parent::__construct('That code has expired. Please request a new one.');
    }
}
