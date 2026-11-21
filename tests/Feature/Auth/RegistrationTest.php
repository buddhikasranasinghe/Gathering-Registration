<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\OneTimePasscode;
use App\Identity\Entities\User;
use App\Identity\IO\Mail\OneTimePasscodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class RegistrationTest extends TestCase
{
    #[Test]
    public function when_a_guest_opens_the_registration_page_then_the_form_is_shown(): void
    {
        $response = $this->get('/register');

        $this->assertRegistrationFormIsShown($response);
    }

    #[Test]
    public function when_a_new_person_registers_then_a_code_is_emailed_and_the_name_is_held(): void
    {
        Mail::fake();

        $response = $this->post('/register', [
            'name' => 'Buddhika Ranasinghe',
            'email' => 'buddhika@example.com',
        ]);

        $this->assertPendingRegistrationStarted($response, 'Buddhika Ranasinghe');
    }

    #[Test]
    public function when_the_email_already_has_an_account_then_registration_is_rejected(): void
    {
        Mail::fake();
        $existingUser = User::factory()->create(['name' => 'Original Name']);

        $response = $this->post('/register', [
            'name' => 'New Name',
            'email' => $existingUser->email,
        ]);

        $this->assertExistingAccountProtected($response, $existingUser);
    }

    #[DataProvider('invalidRegistrations')]
    #[Test]
    public function when_the_details_are_invalid_then_registration_is_rejected(
        array $payload,
        array $invalidFields,
    ): void {
        Mail::fake();

        $response = $this->post('/register', $payload);

        $this->assertInvalidDetailsRejected($response, $invalidFields);
    }

    #[Test]
    public function when_a_code_was_recently_requested_then_registration_is_rejected(): void
    {
        Mail::fake();
        $payload = ['name' => 'Buddhika', 'email' => 'buddhika@example.com'];
        $this->post('/register', $payload);

        $response = $this->post('/register', $payload);

        $this->assertRegistrationCooldownEnforced($response);
    }

    /**
     * @return array<string, array{array<string, string>, array<int, string>}>
     */
    public static function invalidRegistrations(): array
    {
        return [
            'missing name and email' => [[], ['name', 'email']],
            'malformed email' => [
                ['name' => 'Buddhika', 'email' => 'not-an-email'],
                ['email'],
            ],
        ];
    }

    private function assertRegistrationFormIsShown(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertSee('Create your account');
    }

    private function assertPendingRegistrationStarted(TestResponse $response, string $name): void
    {
        $email = 'buddhika@example.com';

        $response->assertRedirect(route('verify'));
        $response->assertSessionHas('auth.otp.email', $email);

        $passcode = OneTimePasscode::latestFor($email);

        $this->assertSame($email, $passcode->email);
        $this->assertSame($name, $passcode->pending_name);
        $this->assertDatabaseMissing('users', ['email' => $email]);
        Mail::assertSent(
            OneTimePasscodeMail::class,
            fn (OneTimePasscodeMail $mail): bool => $mail->hasTo($email),
        );
    }

    private function assertExistingAccountProtected(TestResponse $response, User $existingUser): void
    {
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $response->assertSessionHasInput('email', $existingUser->email);

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('one_time_passcodes', 0);
        $this->assertSame('Original Name', $existingUser->refresh()->name);
        Mail::assertNothingSent();
    }

    private function assertInvalidDetailsRejected(TestResponse $response, array $invalidFields): void
    {
        $response->assertSessionHasErrors($invalidFields);
        Mail::assertNothingSent();
    }

    private function assertRegistrationCooldownEnforced(TestResponse $response): void
    {
        $response->assertSessionHasErrors('email');
        Mail::assertSentCount(1);
        $this->assertDatabaseCount('one_time_passcodes', 1);
    }
}
