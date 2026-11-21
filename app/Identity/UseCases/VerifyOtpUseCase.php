<?php

declare(strict_types=1);

namespace App\Identity\UseCases;

use App\Identity\Entities\OneTimePasscode;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Identity\Exceptions\AccountNotFoundException;
use App\Identity\Exceptions\OtpAttemptsExceededException;
use App\Identity\Exceptions\OtpExpiredException;
use App\Identity\Exceptions\OtpInvalidException;
use App\Identity\UseCases\Requests\VerifyOtpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class VerifyOtpUseCase
{
    /**
     * Verify the code and either sign in the existing account or create the
     * account that was started through registration.
     *
     * @throws \Throwable
     */
    public function handle(VerifyOtpRequest $request): User
    {
        $passcode = $this->verifyCode($request->email, $request->code);

        $user = DB::transaction(function () use ($passcode, $request): ?User {
            $user = $this->findOrCreateUser($request, $this->claimPasscode($passcode));

            if ($user !== null) {
                $this->markEmailVerified($user);
            }

            return $user;
        });

        if ($user === null) {
            throw new AccountNotFoundException;
        }

        return $user;
    }

    /**
     * @throws OtpInvalidException
     * @throws OtpExpiredException
     * @throws OtpAttemptsExceededException
     */
    private function verifyCode(string $email, string $code): OneTimePasscode
    {
        $passcode = $this->activePasscode($email);

        $this->ensureNotExpired($passcode);
        $this->ensureAttemptsRemain($passcode);
        $this->ensureCodeMatches($passcode, $code);

        return $passcode;
    }

    /**
     * @throws OtpInvalidException
     */
    private function activePasscode(string $email): OneTimePasscode
    {
        $passcode = OneTimePasscode::latestFor($email);

        if ($passcode === null || $passcode->isConsumed()) {
            throw new OtpInvalidException;
        }

        return $passcode;
    }

    /**
     * @throws OtpExpiredException
     */
    private function ensureNotExpired(OneTimePasscode $passcode): void
    {
        if ($passcode->isExpired()) {
            throw new OtpExpiredException;
        }
    }

    /**
     * @throws OtpAttemptsExceededException
     */
    private function ensureAttemptsRemain(OneTimePasscode $passcode): void
    {
        if (! $passcode->hasExhaustedAttempts($this->maxAttempts())) {
            return;
        }

        $passcode->consume();

        throw new OtpAttemptsExceededException;
    }

    /**
     * @throws OtpInvalidException
     * @throws OtpAttemptsExceededException
     */
    private function ensureCodeMatches(OneTimePasscode $passcode, string $code): void
    {
        if (Hash::check($code, $passcode->code_hash)) {
            return;
        }

        $passcode->recordFailedAttempt();
        $this->ensureAttemptsRemain($passcode);

        throw new OtpInvalidException;
    }

    /**
     * @throws OtpInvalidException
     */
    private function claimPasscode(OneTimePasscode $passcode): OneTimePasscode
    {
        $locked = OneTimePasscode::query()
            ->whereKey($passcode->getKey())
            ->lockForUpdate()
            ->first();

        if ($locked === null || $locked->isConsumed()) {
            throw new OtpInvalidException;
        }

        $locked->consume();

        return $locked;
    }

    private function findOrCreateUser(VerifyOtpRequest $request, OneTimePasscode $passcode): ?User
    {
        $user = $this->findUser($request->email);

        if ($user !== null) {
            return $user;
        }

        if ($passcode->pending_name === null) {
            return null;
        }

        return $this->createUser($request->email, $passcode->pending_name);
    }

    private function findUser(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    private function createUser(string $email, string $name): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'role' => UserRole::User,
        ]);
    }

    private function markEmailVerified(User $user): void
    {
        if ($user->email_verified_at === null) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
    }

    private function maxAttempts(): int
    {
        return (int) config('otp.max_attempts');
    }
}
