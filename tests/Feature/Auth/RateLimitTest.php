<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\Test;

class RateLimitTest extends TestCase
{
    #[Test]
    public function when_one_email_requests_many_codes_then_further_requests_are_refused(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $this->requestCodeTimes($user->email, 5);

        $response = $this->post('/login', ['email' => $user->email]);

        $this->assertSignInRequestThrottled($response);
    }

    #[Test]
    public function when_many_people_share_one_network_then_further_code_requests_are_refused(): void
    {
        Mail::fake();
        $this->registerPersonTimes(10);

        $response = $this->post('/register', [
            'name' => 'User 11',
            'email' => 'user11@example.com',
        ]);

        $this->assertRegistrationThrottled($response);
    }

    #[Test]
    public function when_one_email_tries_many_codes_then_further_attempts_are_refused(): void
    {
        Mail::fake();
        $this->post('/register', ['name' => 'Buddhika', 'email' => 'buddhika@example.com']);
        $this->attemptCodeTimes(5);

        $response = $this->post('/verify', ['code' => '000000']);

        $this->assertVerificationThrottled($response);
    }

    private function requestCodeTimes(string $email, int $times): void
    {
        for ($request = 1; $request <= $times; $request++) {
            $this->post('/login', ['email' => $email]);
        }
    }

    private function registerPersonTimes(int $times): void
    {
        for ($person = 1; $person <= $times; $person++) {
            $this->post('/register', [
                'name' => 'User '.$person,
                'email' => "user{$person}@example.com",
            ]);
        }
    }

    private function attemptCodeTimes(int $times): void
    {
        for ($attempt = 1; $attempt <= $times; $attempt++) {
            $this->post('/verify', ['code' => '000000']);
        }
    }

    private function assertSignInRequestThrottled(TestResponse $response): void
    {
        $this->assertRequestsThrottled($response, 'email');
        Mail::assertSentCount(1);
    }

    private function assertRegistrationThrottled(TestResponse $response): void
    {
        $this->assertRequestsThrottled($response, 'email');
    }

    private function assertVerificationThrottled(TestResponse $response): void
    {
        $this->assertRequestsThrottled($response, 'code');
        $this->assertGuest();
    }

    private function assertRequestsThrottled(TestResponse $response, string $field): void
    {
        $response->assertSessionHasErrors($field);
        $this->assertStringContainsString(
            'Too many attempts',
            session('errors')->first($field),
        );
    }
}
