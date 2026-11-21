<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Identity\IO\Mail\OneTimePasscodeMail;
use Illuminate\Support\Facades\Mail;

trait InteractsWithOtp
{
    /**
     * Return the code from the most recently sent passcode email.
     */
    protected function latestOtpCode(): string
    {
        $mail = Mail::sent(OneTimePasscodeMail::class)->last();

        $this->assertNotNull($mail, 'No one-time passcode email was sent.');

        return $mail->code;
    }
}
