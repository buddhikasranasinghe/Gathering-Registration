<?php

declare(strict_types=1);

namespace App\Identity\Exceptions;

final class OtpCooldownException extends OtpException
{
    public function __construct(public readonly int $secondsRemaining)
    {
        parent::__construct("Please wait {$secondsRemaining} seconds before requesting another code.");
    }
}
