<?php

declare(strict_types=1);

namespace App\Identity\IO\Http;

use Illuminate\Contracts\Session\Session;

final class OtpSession
{
    private const EMAIL_KEY = 'auth.otp.email';

    public static function put(Session $session, string $email): void
    {
        $session->put(self::EMAIL_KEY, $email);
    }

    public static function email(Session $session): ?string
    {
        $email = $session->get(self::EMAIL_KEY);

        return is_string($email) && $email !== '' ? $email : null;
    }

    public static function forget(Session $session): void
    {
        $session->forget(self::EMAIL_KEY);
    }
}
