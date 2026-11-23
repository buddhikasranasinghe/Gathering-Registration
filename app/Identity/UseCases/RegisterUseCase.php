<?php

declare(strict_types=1);

namespace App\Identity\UseCases;

use App\Identity\Entities\User;
use App\Identity\Exceptions\AccountAlreadyExistsException;
use App\Identity\Exceptions\OtpCooldownException;
use App\Identity\Exceptions\ParticipantNotFoundException;
use App\Identity\Services\OtpCooldown;
use App\Identity\Services\OtpIssuer;
use App\Identity\UseCases\Requests\RegisterRequest;
use App\Participation\Entities\Participant;

final class RegisterUseCase
{
    public function __construct(
        private readonly OtpIssuer $issuer,
        private readonly OtpCooldown $cooldown,
    ) {}

    /**
     * @throws AccountAlreadyExistsException
     * @throws OtpCooldownException
     * @throws ParticipantNotFoundException
     */
    public function handle(RegisterRequest $request): void
    {
        $this->ensureEmailIsAvailable($request->email);
        $this->ensureParticipantExists($request->email);
        $this->cooldown->ensureCanResend($request->email);

        $this->issuer->issue($request->email, $request->name, $request->ipAddress);
    }

    /**
     * @throws ParticipantNotFoundException
     */
    private function ensureParticipantExists(string $email): void
    {
        if (! Participant::query()->where('email', $email)->exists()) {
            throw new ParticipantNotFoundException;
        }
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
