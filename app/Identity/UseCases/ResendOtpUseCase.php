<?php

declare(strict_types=1);

namespace App\Identity\UseCases;

use App\Identity\Entities\OneTimePasscode;
use App\Identity\Exceptions\OtpCooldownException;
use App\Identity\Services\OtpCooldown;
use App\Identity\Services\OtpIssuer;
use App\Identity\UseCases\Requests\ResendOtpRequest;

final class ResendOtpUseCase
{
    public function __construct(
        private readonly OtpIssuer $issuer,
        private readonly OtpCooldown $cooldown,
    ) {}

    /**
     * @throws OtpCooldownException
     */
    public function handle(ResendOtpRequest $request): void
    {
        $passcode = OneTimePasscode::latestFor($request->email);

        if ($passcode === null) {
            return;
        }

        $this->cooldown->ensureCanResend($request->email);

        $this->issuer->issue($request->email, $passcode->pending_name, $request->ipAddress);
    }
}
