<?php

declare(strict_types=1);

namespace App\Identity\UseCases;

use App\Identity\Entities\User;
use App\Identity\Exceptions\AccountAlreadyExistsException;
use App\Identity\Exceptions\OtpCooldownException;
use App\Identity\Services\OtpCooldown;
use App\Identity\Services\OtpIssuer;
use App\Identity\UseCases\Requests\RegisterRequest;

final class RegisterUseCase
{
    public function __construct(
        private readonly OtpIssuer $issuer,
        private readonly OtpCooldown $cooldown,
    ) {}

    /**
     * @throws AccountAlreadyExistsException
     * @throws OtpCooldownException
     */
    public function handle(RegisterRequest $request): void
    {
        $this->ensureEmailIsAvailable($request->email);
        $this->cooldown->ensureCanResend($request->email);

        $this->issuer->issue($request->email, $request->name, $request->ipAddress);
    }

    /**
     * @throws AccountAlreadyExistsException
     */
    private function ensureEmailIsAvailable(string $email): void
    {
        if (User::query()->where('email', $email)->exists()) {
            throw new AccountAlreadyExistsException;
        }
    }
}
