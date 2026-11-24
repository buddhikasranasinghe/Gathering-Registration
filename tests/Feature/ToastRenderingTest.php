<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use PHPUnit\Framework\Attributes\Test;

final class ToastRenderingTest extends TestCase
{
    #[Test]
    public function when_a_success_message_is_flashed_then_a_success_toast_is_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['status' => 'Payment recorded.'])
            ->get('/home');

        $response->assertOk();
        $response->assertSee('data-toast-stack', false);
        $response->assertSee('data-toast-tone="success"', false);
        $response->assertSee('role="status"', false);
        $response->assertSee('data-toast-duration="5000"', false);
        $response->assertSee('Payment recorded.');
    }

    #[Test]
    public function when_validation_fails_then_an_error_toast_is_rendered(): void
    {
        $response = $this->from('/login')
            ->followingRedirects()
            ->post('/login', ['email' => 'not-an-email']);

        $response->assertOk();
        $response->assertSee('data-toast-stack', false);
        $response->assertSee('data-toast-tone="error"', false);
        $response->assertSee('role="alert"', false);
        $response->assertSee('data-toast-duration="8000"', false);
        $response->assertSee('The email field must be a valid email address.');
    }
}
