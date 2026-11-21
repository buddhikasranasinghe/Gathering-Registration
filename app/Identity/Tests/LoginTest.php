<?php

declare(strict_types=1);

namespace App\Identity\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\IO\Mail\OneTimePasscodeMail;
use App\Identity\UseCases\LoginUseCase;
use App\Identity\UseCases\Requests\LoginRequest;
use App\Identity\UseCases\Requests\VerifyOtpRequest;
use App\Identity\UseCases\VerifyOtpUseCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InteractsWithOtp;

final class LoginTest extends TestCase
{
    use InteractsWithOtp;

    #[Test]
    public function when_an_account_exists_then_a_sign_in_code_is_emailed(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->startLogin($user->email);

        $this->assertSignInCodeIssuedFor($user);
    }

    #[Test]
    public function when_no_account_exists_then_no_code_is_emailed(): void
    {
        Mail::fake();

        $this->startLogin('nobody@example.com');

        $this->assertNothingWasIssued();
    }

    #[DataProvider('cooldownPeriods')]
    #[Test]
    public function when_a_code_was_recently_requested_then_the_cooldown_decides_whether_another_is_sent(
        int $secondsWaited,
        int $expectedCodeCount,
    ): void {
        Mail::fake();
        $user = User::factory()->create();

        $this->startLogin($user->email);
        $this->travel($secondsWaited)->seconds();
        $this->startLogin($user->email);

        $this->assertCodeCountFor($user, $expectedCodeCount);
    }

    #[Test]
    public function when_a_code_is_issued_then_it_follows_the_passcode_policy(): void
    {
        Mail::fake();
        $this->freezeTime();
        $user = User::factory()->create();

        $this->startLogin($user->email);

        $this->assertPasscodeFollowsThePolicy($user);
    }

    #[Test]
    public function when_a_new_code_is_issued_then_the_previous_code_stops_working(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->startLogin($user->email);
        $this->travel(61)->seconds();
        $this->startLogin($user->email);

        $this->assertOnlyTheLatestCodeIsActive($user);
    }

    #[Test]
    public function when_a_code_was_already_used_then_the_next_sign_in_still_sends_a_code(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->startLogin($user->email);
        app(VerifyOtpUseCase::class)->handle(
            new VerifyOtpRequest($user->email, $this->latestOtpCode()),
        );
        $this->startLogin($user->email);

        $this->assertOnlyTheLatestCodeIsActive($user);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function cooldownPeriods(): array
    {
        return [
            'immediately' => [0, 1],
            'one second before the cooldown ends' => [59, 1],
            'as the cooldown ends' => [60, 2],
            'after the cooldown' => [61, 2],
        ];
    }

    private function startLogin(string $email): void
    {
        app(LoginUseCase::class)->handle(new LoginRequest($email, '127.0.0.1'));
    }

    private function assertSignInCodeIssuedFor(User $user): void
    {
        Mail::assertSent(
            OneTimePasscodeMail::class,
            fn (OneTimePasscodeMail $mail): bool => $mail->hasTo($user->email),
        );

        $passcode = $user->oneTimePasscodes()->sole();

        $this->assertNull($passcode->pending_name);
        $this->assertSame('127.0.0.1', $passcode->ip_address);
    }

    private function assertNothingWasIssued(): void
    {
        Mail::assertNothingSent();
        $this->assertDatabaseCount('one_time_passcodes', 0);
    }

    private function assertCodeCountFor(User $user, int $expectedCodeCount): void
    {
        Mail::assertSentCount($expectedCodeCount);
        $this->assertSame($expectedCodeCount, $user->oneTimePasscodes()->count());
    }

    private function assertPasscodeFollowsThePolicy(User $user): void
    {
        $code = $this->latestOtpCode();
        $passcode = $user->oneTimePasscodes()->sole();

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertNotSame($code, $passcode->code_hash);
        $this->assertTrue(Hash::check($code, $passcode->code_hash));
        $this->assertSame(
            now()->addMinutes(10)->timestamp,
            $passcode->expires_at->timestamp,
        );
    }

    private function assertOnlyTheLatestCodeIsActive(User $user): void
    {
        $passcodes = $user->oneTimePasscodes()->orderBy('id')->get();

        $this->assertCount(2, $passcodes);
        $this->assertNotNull($passcodes[0]->consumed_at);
        $this->assertNull($passcodes[1]->consumed_at);
    }
}
