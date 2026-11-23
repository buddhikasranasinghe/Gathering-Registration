<?php

declare(strict_types=1);

namespace App\Identity\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\OneTimePasscode;
use App\Identity\Exceptions\OtpCooldownException;
use App\Identity\UseCases\RegisterUseCase;
use App\Identity\UseCases\Requests\RegisterRequest;
use App\Identity\UseCases\Requests\ResendOtpRequest;
use App\Identity\UseCases\ResendOtpUseCase;
use App\Participation\Entities\Participant;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;

final class ResendOtpTest extends TestCase
{
    #[Test]
    public function when_a_registration_is_pending_then_resending_keeps_the_name_and_updates_the_ip(): void
    {
        Mail::fake();
        $this->participantFor('buddhika@example.com');
        app(RegisterUseCase::class)->handle(
            new RegisterRequest('Buddhika', 'buddhika@example.com'),
        );
        $this->travel(61)->seconds();

        $this->resendCodeFor('buddhika@example.com', '10.0.0.5');

        $this->assertResentPasscodeKeptThePendingDetails('buddhika@example.com', 'Buddhika');
    }

    #[Test]
    public function when_a_code_was_recently_requested_then_resending_is_rejected(): void
    {
        Mail::fake();
        $this->participantFor('buddhika@example.com');
        app(RegisterUseCase::class)->handle(
            new RegisterRequest('Buddhika', 'buddhika@example.com'),
        );

        try {
            $this->resendCodeFor('buddhika@example.com');
            $this->fail('A resend inside the cooldown should be rejected.');
        } catch (OtpCooldownException) {
            $this->assertResendWasBlocked();
        }
    }

    #[Test]
    public function when_no_code_was_requested_then_resending_does_nothing(): void
    {
        Mail::fake();

        $this->resendCodeFor('nobody@example.com');

        $this->assertNoPasscodeWasIssued();
    }

    private function resendCodeFor(string $email, ?string $ipAddress = null): void
    {
        app(ResendOtpUseCase::class)->handle(new ResendOtpRequest($email, $ipAddress));
    }

    private function participantFor(string $email): void
    {
        Participant::factory()->create(['email' => $email]);
    }

    private function assertResentPasscodeKeptThePendingDetails(string $email, string $name): void
    {
        Mail::assertSentCount(2);

        $passcode = OneTimePasscode::latestFor($email);

        $this->assertSame($name, $passcode->pending_name);
        $this->assertSame('10.0.0.5', $passcode->ip_address);
    }

    private function assertResendWasBlocked(): void
    {
        Mail::assertSentCount(1);
        $this->assertDatabaseCount('one_time_passcodes', 1);
    }

    private function assertNoPasscodeWasIssued(): void
    {
        Mail::assertNothingSent();
        $this->assertDatabaseCount('one_time_passcodes', 0);
    }
}
