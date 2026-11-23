<?php

declare(strict_types=1);

namespace App\Identity\Exceptions;

use RuntimeException;

final class ParticipantNotFoundException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('You must be on the participant list before registering.');
    }
}
