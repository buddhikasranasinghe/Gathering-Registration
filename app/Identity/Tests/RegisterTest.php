<?php

declare(strict_types=1);

namespace App\Identity\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Exceptions\AccountAlreadyExistsException;
use App\Identity\Exceptions\OtpCooldownException;
use App\Identity\Exceptions\ParticipantNotFoundException;
use App\Identity\IO\Mail\OneTimePasscodeMail;
use App\Identity\UseCases\RegisterUseCase;
use App\Identity\UseCases\Requests\RegisterRequest;
use App\Participation\Entities\Participant;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;

final class RegisterTest extends TestCase
{
    #[Test]
    public function when_a_new_person_registers_then_a_code_is_emailed_and_the_name_is_held(): void
    {
        Mail::fake();
        $this->participantFor('buddhika@example.com');

        $this->startRegistration('Buddhika', 'buddhika@example.com');

        $this->assertPendingRegistrationCreatedFor('buddhika@example.com', 'Buddhika');
    }

    #[Test]
    public function when_a_person_is_not_on_the_participant_list_then_registration_is_rejected(): void
    {
        Mail::fake();

        $this->expectException(ParticipantNotFoundException::class);

        $this->startRegistration('Buddhika', 'buddhika@example.com');
    }

    #[Test]
    public function when_the_email_already_has_an_account_then_registration_is_rejected(): void
    {
        Mail::fake();
        $existing = User::factory()->create(['name' => 'Original Name']);

        try {
            $this->startRegistration('New Name', $existing->email);
            $this->fail('Registration should be rejected for an existing account.');
        } catch (AccountAlreadyExistsException $exception) {
            $this->assertExistingAccountWasProtected($exception, $existing);
        }
    }

    #[Test]
    public function when_a_code_was_recently_requested_then_registration_is_rejected(): void
    {
        Mail::fake();
        $this->participantFor('buddhika@example.com');

        $this->startRegistration('Buddhika', 'buddhika@example.com');

        try {
            $this->startRegistration('Buddhika', 'buddhika@example.com');
            $this->fail('A second code inside the cooldown should be rejected.');
        } catch (OtpCooldownException $exception) {
            $this->assertRegistrationCooldownWasEnforced($exception);
        }
    }

    #[Test]
    public function when_the_cooldown_has_ended_then_registration_sends_another_code(): void
    {
        Mail::fake();
        $this->participantFor('buddhika@example.com');

        $this->startRegistration('Buddhika', 'buddhika@example.com');
        $this->travel(61)->seconds();
        $this->startRegistration('Buddhika', 'buddhika@example.com');

        Mail::assertSentCount(2);
    }

    private function startRegistration(string $name, string $email): void
    {
        app(RegisterUseCase::class)->handle(new RegisterRequest($name, $email, '127.0.0.1'));
    }

    private function participantFor(string $email): Participant
    {
        return Participant::factory()->create(['email' => $email]);
    }

    private function assertPendingRegistrationCreatedFor(string $email, string $name): void
    {
        Mail::assertSent(
            OneTimePasscodeMail::class,
            fn (OneTimePasscodeMail $mail): bool => $mail->hasTo($email),
        );

        $this->assertDatabaseHas('one_time_passcodes', [
            'email' => $email,
            'pending_name' => $name,
            'ip_address' => '127.0.0.1',
        ]);
        $this->assertDatabaseMissing('users', ['email' => $email]);
    }

    private function assertExistingAccountWasProtected(
        AccountAlreadyExistsException $exception,
        User $existing,
    ): void {
        $this->assertSame(
            'An account with this email already exists. Please sign in instead.',
            $exception->getMessage(),
        );
        Mail::assertNothingSent();
        $this->assertDatabaseCount('one_time_passcodes', 0);
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('Original Name', $existing->refresh()->name);
    }

    private function assertRegistrationCooldownWasEnforced(OtpCooldownException $exception): void
    {
        $this->assertSame(60, $exception->secondsRemaining);
        $this->assertSame(
            'Please wait 60 seconds before requesting another code.',
            $exception->getMessage(),
        );
        Mail::assertSentCount(1);
        $this->assertDatabaseCount('one_time_passcodes', 1);
    }
}
