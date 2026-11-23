<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\OneTimePasscode;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InteractsWithOtp;

class VerifyOtpTest extends TestCase
{
    use InteractsWithOtp;

    #[Test]
    public function when_no_code_was_requested_then_the_verification_page_sends_the_person_back_to_sign_in(): void
    {
        $response = $this->get('/verify');

        $this->assertSentBackToSignIn($response);
    }

    #[Test]
    public function when_a_code_is_waiting_then_the_verification_page_shows_the_email_and_resend_option(): void
    {
        Mail::fake();
        $this->startRegistration('buddhika@example.com');

        $response = $this->get('/verify');

        $this->assertVerificationFormShown($response, 'buddhika@example.com');
    }

    #[Test]
    public function when_the_code_is_correct_then_the_account_is_created_and_signed_in(): void
    {
        Mail::fake();
        $this->startRegistration('buddhika@example.com');

        $response = $this->post('/verify', ['code' => $this->latestOtpCode()]);

        $this->assertAccountCreatedAndSignedIn($response);
    }

    #[Test]
    public function when_the_code_is_wrong_then_the_person_stays_signed_out_and_the_attempt_is_counted(): void
    {
        Mail::fake();
        $this->startRegistration('buddhika@example.com');
        $wrongCode = $this->incorrectCode($this->latestOtpCode());

        $response = $this->post('/verify', ['code' => $wrongCode]);

        $this->assertWrongCodeRefused($response, 'buddhika@example.com');
    }

    #[Test]
    public function when_the_code_has_expired_then_the_person_is_asked_for_a_new_one(): void
    {
        Mail::fake();
        $this->startRegistration('buddhika@example.com');
        $code = $this->latestOtpCode();
        $this->travel(11)->minutes();

        $response = $this->post('/verify', ['code' => $code]);

        $this->assertCodeRefused(
            $response,
            'That code has expired. Please request a new one.',
        );
    }

    #[Test]
    public function when_too_many_wrong_codes_were_tried_then_even_the_right_code_is_refused(): void
    {
        Mail::fake();
        $this->startRegistration('buddhika@example.com');
        $code = $this->latestOtpCode();
        $wrongCode = $this->incorrectCode($code);
        $this->attemptWrongCode($wrongCode, 5);
        $this->travel(61)->seconds();

        $response = $this->post('/verify', ['code' => $code]);

        $this->assertLockedCodeRefused($response, 'buddhika@example.com');
    }

    #[Test]
    public function when_a_new_code_is_requested_then_the_previous_code_stops_working(): void
    {
        Mail::fake();
        $this->startRegistration('buddhika@example.com');
        $firstCode = $this->latestOtpCode();
        $this->requestNewCode();
        $secondCode = $this->latestOtpCode();

        $response = $this->post('/verify', ['code' => $firstCode]);

        $this->assertPreviousCodeReplaced($response, $firstCode, $secondCode);
    }

    #[Test]
    public function when_a_new_code_is_requested_then_the_new_code_signs_the_person_in(): void
    {
        Mail::fake();
        $this->startRegistration('buddhika@example.com');
        $this->requestNewCode();

        $response = $this->post('/verify', ['code' => $this->latestOtpCode()]);

        $this->assertSignedIn($response);
    }

    private function startRegistration(string $email): void
    {
        Participant::factory()->create(['name' => 'Buddhika', 'email' => $email]);

        $this->post('/register', ['name' => 'Buddhika', 'email' => $email]);
    }

    private function requestNewCode(): void
    {
        $this->travel(61)->seconds();

        $this->post('/verify/resend');
    }

    private function attemptWrongCode(string $wrongCode, int $attempts): void
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            $this->post('/verify', ['code' => $wrongCode]);
        }
    }

    /**
     * Choose a value that is guaranteed to differ from the issued code.
     */
    private function incorrectCode(string $code): string
    {
        return $code === '000000' ? '000001' : '000000';
    }

    private function assertSentBackToSignIn(TestResponse $response): void
    {
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    private function assertVerificationFormShown(TestResponse $response, string $email): void
    {
        $response->assertOk();
        $response->assertSee($email);
        $response->assertSee('Resend code');
    }

    private function assertAccountCreatedAndSignedIn(TestResponse $response): void
    {
        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();

        $user = auth()->user();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('Buddhika', $user->name);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame([UserRole::User->value], $user->roles->pluck('name')->all());
    }

    private function assertWrongCodeRefused(TestResponse $response, string $email): void
    {
        $this->assertCodeRefused($response, 'That code is not valid. Please try again.');
        $this->assertSame(1, OneTimePasscode::latestFor($email)->attempts);
    }

    private function assertCodeRefused(TestResponse $response, string $message): void
    {
        $response->assertSessionHasErrors(['code' => $message]);
        $this->assertGuest();
    }

    private function assertLockedCodeRefused(TestResponse $response, string $email): void
    {
        $this->assertCodeRefused($response, 'That code is not valid. Please try again.');
        $this->assertNotNull(OneTimePasscode::latestFor($email)->consumed_at);
    }

    private function assertPreviousCodeReplaced(
        TestResponse $response,
        string $firstCode,
        string $secondCode,
    ): void {
        Mail::assertSentCount(2);
        $this->assertNotSame($firstCode, $secondCode);
        $this->assertCodeRefused($response, 'That code is not valid. Please try again.');
    }

    private function assertSignedIn(TestResponse $response): void
    {
        $response->assertRedirect(route('home'));
        $this->assertAuthenticated();
    }
}
