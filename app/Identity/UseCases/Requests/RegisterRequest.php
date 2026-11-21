<?php

declare(strict_types=1);

namespace App\Identity\UseCases\Requests;

final readonly class RegisterRequest
{
    public string $name;

    public string $email;

    public function __construct(string $name, string $email, public ?string $ipAddress = null)
    {
        $this->name = trim($name);
        $this->email = mb_strtolower(trim($email));
    }
}
