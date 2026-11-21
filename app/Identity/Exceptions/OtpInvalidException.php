<?php

declare(strict_types=1);

namespace App\Identity\Exceptions;

final class OtpInvalidException extends OtpException
{
    public function __construct()
    {
        parent::__construct('That code is not valid. Please try again.');
    }
}
