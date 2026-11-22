<?php

declare(strict_types=1);

namespace App\Participation\Exceptions;

use RuntimeException;

final class ParticipantImportAlreadyRunningException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('A participant import is already running.');
    }
}
