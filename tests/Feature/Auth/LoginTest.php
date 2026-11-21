<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\IO\Mail\OneTimePasscodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\InteractsWithOtp;

class LoginTest extends TestCase
{
    use InteractsWithOtp;

    #[Test]
    public function when_a_guest_opens_the_sign_in_page_then_the_form_is_shown(): void
    {
        $response = $this->get('/login');

        $this->assertSignInFormIsShown($response);
    }

    #[Test]
    public function when_an_account_exists_then_a_sign_in_code_is_emailed(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $response = $this->post('/login', ['email' => $user->email]);

        $this->assertSignInCodeSentTo($response, $user);
    }

    #[Test]
    public function when_the_email_is_typed_in_a_different_case_then_the_account_is_still_found(): void
    {
        Mail::fake();
        $user = User::factory()->create(['email' => 'buddhika@example.com']);

        $response = $this->post('/login', ['email' => '  Buddhika@Example.COM ']);

        $this->assertAccountFoundRegardlessOfCase($response, $user->email);
    }

    #[Test]
    public function when_no_account_exists_then_the_response_does_not_reveal_it(): void
    {
        Mail::fake();

        $response = $this->post('/login', ['email' => 'nobody@example.com']);

        $this->assertNoAccountWasRevealed($response);
    }

    #[Test]
    public function when_the_email_is_malformed_then_it_is_rejected(): void
    {
        Mail::fake();

        $response = $this->post('/login', ['email' => 'not-an-email']);

        $this->assertMalformedEmailRejected($response);
    }

    #[Test]
    public function when_the_emailed_code_is_entered_then_the_person_is_signed_in(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->post('/login', ['email' => $user->email]);

        $response = $this->post('/verify', ['code' => $this->latestOtpCode()]);

        $this->assertPersonSignedIn($response, $user);
    }

    #[Test]
    public function when_a_signed_in_person_opens_the_sign_in_page_then_they_are_sent_home(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/login');

        $this->assertSentHome($response);
    }

    private function assertSignInFormIsShown(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertSee('Email me a sign-in code');
    }

    private function assertSignInCodeSentTo(TestResponse $response, User $user): void
    {
        $response->assertRedirect(route('verify'));
        $response->assertSessionHas('auth.otp.email', $user->email);
        $this->assertNull($user->oneTimePasscodes()->sole()->pending_name);
        Mail::assertSent(
            OneTimePasscodeMail::class,
            fn (OneTimePasscodeMail $mail): bool => $mail->hasTo($user->email),
        );
    }

    private function assertAccountFoundRegardlessOfCase(TestResponse $response, string $email): void
    {
        $response->assertRedirect(route('verify'));
        $response->assertSessionHas('auth.otp.email', $email);
        Mail::assertSent(
            OneTimePasscodeMail::class,
            fn (OneTimePasscodeMail $mail): bool => $mail->hasTo($email),
        );
    }

    private function assertNoAccountWasRevealed(TestResponse $response): void
    {
        $response->assertRedirect(route('verify'));
        $response->assertSessionHas(
            'status',
            'If an account exists for that email, we have sent a sign-in code.',
        );
        Mail::assertNothingSent();
        $this->assertDatabaseCount('one_time_passcodes', 0);
    }

    private function assertMalformedEmailRejected(TestResponse $response): void
    {
        $response->assertSessionHasErrors('email');
        Mail::assertNothingSent();
    }

    private function assertPersonSignedIn(TestResponse $response, User $user): void
    {
        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
    }

    private function assertSentHome(TestResponse $response): void
    {
        $response->assertRedirect(route('home'));
    }
}
