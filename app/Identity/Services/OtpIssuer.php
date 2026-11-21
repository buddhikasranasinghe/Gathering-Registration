<?php

declare(strict_types=1);

namespace App\Identity\Services;

use App\Identity\Entities\OneTimePasscode;
use App\Identity\IO\Mail\OneTimePasscodeMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

/**
 * @phpstan-type PasscodePayload array{
 *     email: string,
 *     code: string,
 *     pendingName: ?string,
 *     ipAddress: ?string,
 *     expiresInMinutes: int
 * }
 */
final class OtpIssuer
{
    /**
     * Issue a fresh code, invalidating any active code for the same email.
     */
    public function issue(string $email, ?string $pendingName = null, ?string $ipAddress = null): void
    {
        $passcode = $this->buildPasscode($email, $pendingName, $ipAddress);

        $this->storePasscode($passcode);
        $this->sendCode($passcode);
    }

    /**
     * @return PasscodePayload
     */
    private function buildPasscode(string $email, ?string $pendingName, ?string $ipAddress): array
    {
        return [
            'email' => $email,
            'code' => $this->generateCode(),
            'pendingName' => $pendingName,
            'ipAddress' => $ipAddress,
            'expiresInMinutes' => $this->expiryInMinutes(),
        ];
    }

    /**
     * @param  PasscodePayload  $passcode
     */
    private function storePasscode(array $passcode): void
    {
        DB::transaction(function () use ($passcode): void {
            $this->invalidateActiveCodes($passcode['email']);
            $this->createPasscode($passcode);
        });
    }

    private function invalidateActiveCodes(string $email): void
    {
        OneTimePasscode::query()
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);
    }

    /**
     * @param  PasscodePayload  $passcode
     */
    private function createPasscode(array $passcode): void
    {
        OneTimePasscode::create([
            'email' => $passcode['email'],
            'code_hash' => Hash::make($passcode['code']),
            'pending_name' => $passcode['pendingName'],
            'attempts' => 0,
            'expires_at' => now()->addMinutes($passcode['expiresInMinutes']),
            'ip_address' => $passcode['ipAddress'],
        ]);
    }

    /**
     * @param  PasscodePayload  $passcode
     */
    private function sendCode(array $passcode): void
    {
        Mail::to($passcode['email'])->send(
            new OneTimePasscodeMail($passcode['code'], $passcode['expiresInMinutes']),
        );
    }

    private function expiryInMinutes(): int
    {
        return (int) config('otp.expires_after_minutes');
    }

    private function generateCode(): string
    {
        $length = (int) config('otp.code_length');
        $highest = (10 ** $length) - 1;

        return str_pad((string) random_int(0, $highest), $length, '0', STR_PAD_LEFT);
    }
}
