<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

class HomeTest extends TestCase
{
    #[Test]
    public function when_the_root_is_visited_then_the_person_is_sent_to_home(): void
    {
        $response = $this->get('/');

        $this->assertSentToHome($response);
    }

    #[Test]
    public function when_a_guest_visits_home_then_they_are_sent_to_sign_in(): void
    {
        $response = $this->get('/home');

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function when_a_signed_in_person_visits_home_then_their_details_are_shown(): void
    {
        $user = User::factory()->admin()->create(['name' => 'Admin Person']);

        $response = $this->actingAs($user)->get('/home');

        $this->assertSignedInDetailsShown($response, $user);
    }

    #[DataProvider('countdownStates')]
    #[Test]
    public function when_a_signed_in_person_visits_home_then_the_remaining_days_are_shown(
        string $today,
        string $functionDate,
        array $expectedTexts,
    ): void {
        config(['gathering.function_date' => $functionDate]);
        $this->travelTo($today);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/home');

        $this->assertCountdownShown($response, $expectedTexts);
    }

    #[Test]
    public function when_a_name_contains_script_tags_then_it_is_shown_as_text(): void
    {
        $user = User::factory()->create(['name' => '<script>alert("xss")</script>']);

        $response = $this->actingAs($user)->get('/home');

        $this->assertNameIsEscaped($response);
    }

    #[Test]
    public function when_a_person_signs_out_then_the_session_ends(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertSessionEnded($response);
    }

    /**
     * @return array<string, array{string, string, array<int, string>}>
     */
    public static function countdownStates(): array
    {
        return [
            'days remaining' => ['2026-11-21', '2026-12-12', ['21', 'days to go']],
            'one day remaining' => ['2026-12-11', '2026-12-12', ['1', 'day to go']],
            'function is today' => ['2026-12-12', '2026-12-12', ['The function is today.']],
            'function has passed' => ['2026-12-13', '2026-12-12', ['The function has passed.']],
        ];
    }

    private function assertSentToHome(TestResponse $response): void
    {
        $response->assertRedirect('/home');
    }

    private function assertSignedInDetailsShown(TestResponse $response, User $user): void
    {
        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee($user->email);
        $response->assertSee('Treasurer — '.$user->batch->name);
    }

    private function assertNameIsEscaped(TestResponse $response): void
    {
        $response->assertOk();
        $response->assertDontSee('<script>alert("xss")</script>', false);
        $response->assertSee('&lt;script&gt;', false);
    }

    private function assertSessionEnded(TestResponse $response): void
    {
        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    private function assertCountdownShown(TestResponse $response, array $expectedTexts): void
    {
        $response->assertOk();
        $response->assertSeeText('Countdown');

        foreach ($expectedTexts as $text) {
            $response->assertSeeText($text);
        }
    }
}
