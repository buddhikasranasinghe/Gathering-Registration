<?php

declare(strict_types=1);

namespace App\Identity\UseCases\Requests;

final readonly class ResendOtpRequest
{
    public string $email;

    public function __construct(string $email, public ?string $ipAddress = null)
    {
        $this->email = mb_strtolower(trim($email));
    }
}
