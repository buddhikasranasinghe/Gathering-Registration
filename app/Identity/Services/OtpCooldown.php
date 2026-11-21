<?php

declare(strict_types=1);

namespace App\Identity\Services;

use App\Identity\Entities\OneTimePasscode;
use App\Identity\Exceptions\OtpCooldownException;

final class OtpCooldown
{
    /**
     * Seconds left before another code may be requested for this email.
     */
    public function secondsUntilResend(string $email): int
    {
        return $this->remainingSeconds(OneTimePasscode::latestFor($email));
    }

    /**
     * A consumed challenge no longer blocks a new request, otherwise a user
     * who signs out and immediately signs in again would get no code at all.
     *
     * @throws OtpCooldownException
     */
    public function ensureCanResend(string $email): void
    {
        $seconds = $this->secondsUntilResend($email);

        if ($seconds > 0) {
            throw new OtpCooldownException($seconds);
        }
    }

    private function remainingSeconds(?OneTimePasscode $passcode): int
    {
        if ($passcode === null || $passcode->isConsumed()) {
            return 0;
        }

        return $passcode->secondsUntilResend((int) config('otp.resend_cooldown_seconds'));
    }
}
