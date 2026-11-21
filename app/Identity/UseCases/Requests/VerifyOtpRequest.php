<?php

declare(strict_types=1);

namespace App\Identity\UseCases\Requests;

final readonly class VerifyOtpRequest
{
    public string $email;

    public string $code;

    public function __construct(string $email, string $code)
    {
        $this->email = mb_strtolower(trim($email));
        $this->code = trim($code);
    }
}
