<?php

declare(strict_types=1);

namespace App\Identity\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\OneTimePasscode;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Identity\Exceptions\AccountNotFoundException;
use App\Identity\Exceptions\OtpAttemptsExceededException;
use App\Identity\Exceptions\OtpExpiredException;
use App\Identity\Exceptions\OtpInvalidException;
use App\Identity\Services\OtpIssuer;
use App\Identity\UseCases\LoginUseCase;
use App\Identity\UseCases\RegisterUseCase;
use App\Identity\UseCases\Requests\LoginRequest;
use App\Identity\UseCases\Requests\RegisterRequest;
use App\Identity\UseCases\Requests\VerifyOtpRequest;
use App\Identity\UseCases\VerifyOtpUseCase;
use App\Participation\Entities\Participant;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InteractsWithOtp;

final class VerifyOtpTest extends TestCase
{
    use InteractsWithOtp;

    #[Test]
    public function when_an_existing_account_verifies_then_it_signs_in_with_a_verified_email(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $this->startLogin($user->email);

        $signedIn = $this->verifyCodeFor($user->email, $this->latestOtpCode());

        $this->assertExistingAccountSignedIn($user, $signedIn);
    }

    #[Test]
    public function when_a_registration_code_verifies_then_the_account_is_created_with_the_pending_name(): void
    {
        Mail::fake();
        $this->startRegistration('Buddhika', 'buddhika@example.com');

        $user = $this->verifyCodeFor('buddhika@example.com', $this->latestOtpCode());

        $this->assertAccountCreatedFromRegistration($user);
    }

    #[Test]
    public function when_a_registration_code_verifies_then_the_participant_record_is_created(): void
    {
        Mail::fake();
        $this->startRegistration('Buddhika', 'buddhika@example.com');

        $this->verifyCodeFor('buddhika@example.com', $this->latestOtpCode());

        $this->assertParticipantWasRegistered('buddhika@example.com', 'Buddhika');
    }

    #[Test]
    public function when_a_privileged_account_verifies_then_no_participant_is_created(): void
    {
        Mail::fake();
        $superAdmin = User::factory()->superAdmin()->unverified()->create();
        $this->startLogin($superAdmin->email);

        $this->verifyCodeFor($superAdmin->email, $this->latestOtpCode());

        $this->assertNoParticipantWasCreated();
    }

    #[Test]
    public function when_a_registered_participant_signs_in_again_then_the_participant_is_not_duplicated(): void
    {
        Mail::fake();
        $this->startRegistration('Buddhika', 'buddhika@example.com');
        $this->verifyCodeFor('buddhika@example.com', $this->latestOtpCode());

        $this->startLogin('buddhika@example.com');
        $this->verifyCodeFor('buddhika@example.com', $this->latestOtpCode());

        $this->assertSingleParticipantFor('buddhika@example.com', 'Buddhika');
    }

    #[Test]
    public function when_a_participant_record_already_has_the_email_then_it_is_claimed(): void
    {
        Mail::fake();
        $participant = Participant::factory()->create([
            'name' => 'Imported Name',
            'email' => 'buddhika@example.com',
        ]);
        $this->startRegistration('Buddhika', 'buddhika@example.com');

        $this->verifyCodeFor('buddhika@example.com', $this->latestOtpCode());

        $this->assertExistingParticipantWasClaimed($participant);
    }

    #[Test]
    public function when_an_existing_account_verifies_then_its_name_is_unchanged(): void
    {
        Mail::fake();
        $existing = User::factory()->create(['name' => 'Original Name']);
        $this->startLogin($existing->email);

        $this->verifyCodeFor($existing->email, $this->latestOtpCode());

        $this->assertAccountWasReused($existing);
    }

    #[Test]
    public function when_the_code_is_wrong_then_the_attempt_is_recorded_and_the_code_stays_open(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->startLogin($user->email);
        $wrongCode = $this->incorrectCode($this->latestOtpCode());

        try {
            $this->verifyCodeFor($user->email, $wrongCode);
            $this->fail('A wrong code should not be accepted.');
        } catch (OtpInvalidException) {
            $this->assertFailedAttemptRecorded($user);
        }
    }

    #[Test]
    public function when_the_code_is_wrong_too_many_times_then_it_is_locked(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->startLogin($user->email);
        $wrongCode = $this->incorrectCode($this->latestOtpCode());

        $this->attemptWrongCode($user->email, $wrongCode, 4);

        try {
            $this->verifyCodeFor($user->email, $wrongCode);
            $this->fail('The fifth wrong attempt should lock the code.');
        } catch (OtpAttemptsExceededException) {
            $this->assertCodeLockedAfterTooManyAttempts($user);
        }
    }

    #[Test]
    public function when_the_code_was_already_used_then_verification_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->startLogin($user->email);
        $request = new VerifyOtpRequest($user->email, $this->latestOtpCode());
        $this->verifyCode($request);

        $this->expectException(OtpInvalidException::class);

        $this->verifyCode($request);
    }

    #[Test]
    public function when_the_code_has_expired_then_verification_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->startLogin($user->email);
        $code = $this->latestOtpCode();
        $this->travel(11)->minutes();

        $this->expectException(OtpExpiredException::class);

        $this->verifyCodeFor($user->email, $code);
    }

    #[Test]
    public function when_no_code_was_requested_then_verification_is_rejected(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->expectException(OtpInvalidException::class);

        $this->verifyCodeFor($user->email, '123456');
    }

    #[Test]
    public function when_no_account_or_name_is_waiting_then_verification_is_rejected(): void
    {
        Mail::fake();
        app(OtpIssuer::class)->issue('buddhika@example.com');

        try {
            $this->verifyCodeFor('buddhika@example.com', $this->latestOtpCode());
            $this->fail('Verification without an account or pending name should not create an account.');
        } catch (AccountNotFoundException) {
            $this->assertNoAccountWasCreated('buddhika@example.com');
        }
    }

    private function startLogin(string $email): void
    {
        app(LoginUseCase::class)->handle(new LoginRequest($email));
    }

    private function startRegistration(string $name, string $email): void
    {
        app(RegisterUseCase::class)->handle(new RegisterRequest($name, $email));
    }

    private function verifyCodeFor(string $email, string $code): User
    {
        return $this->verifyCode(new VerifyOtpRequest($email, $code));
    }

    private function verifyCode(VerifyOtpRequest $request): User
    {
        return app(VerifyOtpUseCase::class)->handle($request);
    }

    private function attemptWrongCode(string $email, string $wrongCode, int $attempts): void
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $this->verifyCodeFor($email, $wrongCode);
                $this->fail('A wrong code should not be accepted.');
            } catch (OtpInvalidException) {
                continue;
            }
        }
    }

    /**
     * Choose a value that is guaranteed to differ from the issued code.
     */
    private function incorrectCode(string $code): string
    {
        return $code === '000000' ? '000001' : '000000';
    }

    private function assertExistingAccountSignedIn(User $account, User $signedIn): void
    {
        $this->assertTrue($signedIn->is($account));

        $account->refresh();
        $this->assertNotNull($account->email_verified_at);
        $this->assertNotNull($account->oneTimePasscodes()->sole()->consumed_at);
    }

    private function assertAccountCreatedFromRegistration(User $user): void
    {
        $this->assertSame('Buddhika', $user->name);
        $this->assertSame('buddhika@example.com', $user->email);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame(
            [UserRole::User->value],
            $user->roles->pluck('name')->all(),
        );
        $this->assertDatabaseCount('users', 1);
    }

    private function assertAccountWasReused(User $existing): void
    {
        $this->assertSame('Original Name', $existing->refresh()->name);
        $this->assertDatabaseCount('users', 1);
    }

    private function assertFailedAttemptRecorded(User $user): void
    {
        $passcode = $user->oneTimePasscodes()->sole();

        $this->assertSame(1, $passcode->attempts);
        $this->assertNull($passcode->consumed_at);
    }

    private function assertCodeLockedAfterTooManyAttempts(User $user): void
    {
        $passcode = $user->oneTimePasscodes()->sole();

        $this->assertSame(5, $passcode->attempts);
        $this->assertNotNull($passcode->consumed_at);
    }

    private function assertNoAccountWasCreated(string $email): void
    {
        $this->assertDatabaseMissing('users', ['email' => $email]);
        $this->assertNotNull(OneTimePasscode::latestFor($email)->consumed_at);
    }

    private function assertParticipantWasRegistered(string $email, string $name): void
    {
        $participant = Participant::query()->where('email', $email)->sole();

        $this->assertSame($name, $participant->name);
        $this->assertNull($participant->import_key);
    }

    private function assertNoParticipantWasCreated(): void
    {
        $this->assertSame(0, Participant::query()->count());
    }

    private function assertSingleParticipantFor(string $email, string $name): void
    {
        $participants = Participant::query()->where('email', $email)->get();

        $this->assertCount(1, $participants);
        $this->assertSame($name, $participants->first()->name);
    }

    private function assertExistingParticipantWasClaimed(Participant $participant): void
    {
        $participant->refresh();

        $this->assertSame(1, Participant::query()->count());
        $this->assertSame('Imported Name', $participant->name);
        $this->assertSame('buddhika@example.com', $participant->email);
    }
}
