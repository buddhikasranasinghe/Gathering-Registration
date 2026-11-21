<?php

declare(strict_types=1);

namespace App\Identity\UseCases;

use App\Identity\Entities\User;
use App\Identity\Services\OtpCooldown;
use App\Identity\Services\OtpIssuer;
use App\Identity\UseCases\Requests\LoginRequest;

final class LoginUseCase
{
    public function __construct(
        private readonly OtpIssuer $issuer,
        private readonly OtpCooldown $cooldown,
    ) {}

    /**
     * Email a sign-in code only when the account exists. The delivery layer
     * always returns the same response so accounts cannot be enumerated.
     */
    public function handle(LoginRequest $request): void
    {
        if (! $this->accountExists($request->email)) {
            return;
        }

        if ($this->isCoolingDown($request->email)) {
            return;
        }

        $this->issuer->issue($request->email, ipAddress: $request->ipAddress);
    }

    private function accountExists(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    private function isCoolingDown(string $email): bool
    {
        return $this->cooldown->secondsUntilResend($email) > 0;
    }
}
