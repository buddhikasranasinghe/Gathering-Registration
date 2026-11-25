<?php

declare(strict_types=1);

namespace App\Identity\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\Test;

final class CreateUserFromParticipantTest extends TestCase
{
    #[Test]
    public function when_a_super_admin_creates_a_user_from_a_participant_then_the_account_is_created(): void
    {
        Mail::fake();
        $superAdmin = User::factory()->superAdmin()->create();
        $batch = Batch::factory()->create();
        $participant = Participant::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.test',
            'batch_id' => $batch->getKey(),
        ]);

        $response = $this->actingAs($superAdmin)->post(
            route('participants.account.store', $participant),
            ['email' => 'jane@example.test'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'User account created for Jane Doe. They can sign in with jane@example.test.');

        $user = User::query()->where('email', 'jane@example.test')->sole();

        $this->assertSame('Jane Doe', $user->name);
        $this->assertSame(UserRole::User, $user->role);
        $this->assertSame($batch->getKey(), $user->batch_id);
        $this->assertNull($user->password);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue($user->hasRole(UserRole::User->value));
        Mail::assertNothingSent();
    }

    #[Test]
    public function when_a_participant_has_no_email_then_the_super_admin_can_add_one_and_create_the_account(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $participant = Participant::factory()->create([
            'name' => 'No Email Participant',
            'email' => null,
        ]);

        $response = $this->actingAs($superAdmin)->post(
            route('participants.account.store', $participant),
            ['email' => 'no-email@example.test'],
        );

        $response->assertRedirect();

        $this->assertSame('no-email@example.test', $participant->refresh()->email);
        $this->assertSame('No Email Participant', User::query()->where('email', 'no-email@example.test')->sole()->name);
    }

    #[Test]
    public function when_a_participant_already_has_an_account_then_no_duplicate_is_created(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $participant = Participant::factory()->create(['email' => 'existing@example.test']);
        User::factory()->create(['email' => 'existing@example.test']);

        $response = $this->actingAs($superAdmin)->post(
            route('participants.account.store', $participant),
            ['email' => 'existing@example.test'],
        );

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::query()->where('email', 'existing@example.test')->count());
    }

    #[Test]
    public function when_an_account_exists_then_changing_the_participant_email_does_not_create_another_account(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $participant = Participant::factory()->create(['email' => 'old@example.test']);
        User::factory()->create(['email' => 'old@example.test']);

        $response = $this->actingAs($superAdmin)->post(
            route('participants.account.store', $participant),
            ['email' => 'new@example.test'],
        );

        $response->assertSessionHasErrors('participant');
        $this->assertSame(1, User::query()->where('email', 'old@example.test')->count());
        $this->assertSame(0, User::query()->where('email', 'new@example.test')->count());
        $this->assertSame('old@example.test', $participant->refresh()->email);
    }

    #[Test]
    public function when_the_email_belongs_to_another_participant_then_the_request_is_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $participant = Participant::factory()->create(['email' => null]);
        Participant::factory()->create(['email' => 'taken@example.test']);

        $response = $this->actingAs($superAdmin)->post(
            route('participants.account.store', $participant),
            ['email' => 'taken@example.test'],
        );

        $response->assertSessionHasErrors('email');
        $this->assertSame(0, User::query()->where('email', 'taken@example.test')->count());
        $this->assertNull($participant->refresh()->email);
    }

    #[Test]
    public function when_an_admin_tries_to_create_a_user_then_access_is_forbidden(): void
    {
        $admin = User::factory()->admin()->create();
        $participant = Participant::factory()->create(['email' => null]);

        $response = $this->actingAs($admin)->post(
            route('participants.account.store', $participant),
            ['email' => 'admin-blocked@example.test'],
        );

        $response->assertForbidden();
        $this->assertSame(0, User::query()->where('email', 'admin-blocked@example.test')->count());
    }

    #[Test]
    public function when_a_main_treasurer_tries_to_create_a_user_then_access_is_forbidden(): void
    {
        $mainTreasurer = User::factory()->mainTreasurer()->create();
        $participant = Participant::factory()->create(['email' => null]);

        $response = $this->actingAs($mainTreasurer)->post(
            route('participants.account.store', $participant),
            ['email' => 'main-treasurer-blocked@example.test'],
        );

        $response->assertForbidden();
        $this->assertSame(0, User::query()->where('email', 'main-treasurer-blocked@example.test')->count());
    }

    #[Test]
    public function when_a_guest_tries_to_create_a_user_then_it_redirects_to_login(): void
    {
        $participant = Participant::factory()->create(['email' => null]);

        $response = $this->post(
            route('participants.account.store', $participant),
            ['email' => 'guest-blocked@example.test'],
        );

        $response->assertRedirect(route('login'));
        $this->assertSame(0, User::query()->where('email', 'guest-blocked@example.test')->count());
    }

    #[Test]
    public function when_an_archived_participant_is_used_then_no_account_is_created(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $participant = Participant::factory()->archived()->create(['email' => null]);

        $response = $this->actingAs($superAdmin)->post(
            route('participants.account.store', $participant),
            ['email' => 'archived@example.test'],
        );

        $response->assertSessionHasErrors('participant');
        $this->assertSame(0, User::query()->where('email', 'archived@example.test')->count());
    }

    #[Test]
    public function when_the_email_is_invalid_then_the_request_is_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $participant = Participant::factory()->create(['email' => null]);

        $response = $this->actingAs($superAdmin)->post(
            route('participants.account.store', $participant),
            ['email' => 'not-an-email'],
        );

        $response->assertSessionHasErrors('email');
        $this->assertSame(0, User::query()->where('email', 'not-an-email')->count());
    }

    #[Test]
    public function when_a_super_admin_views_a_participant_with_an_account_then_the_account_state_is_shown(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $participant = Participant::factory()->create(['email' => 'linked@example.test']);
        User::factory()->create(['email' => 'linked@example.test']);

        $response = $this->actingAs($superAdmin)->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertSee('User account exists');
        $response->assertSee('linked@example.test');
        $response->assertDontSee('Create user account');
    }

    #[Test]
    public function when_an_admin_views_a_participant_then_the_account_card_is_hidden(): void
    {
        $admin = User::factory()->admin()->create();
        $participant = Participant::factory()->create([
            'email' => null,
            'batch_id' => $admin->batch_id,
        ]);

        $response = $this->actingAs($admin)->get(route('participants.show', $participant));

        $response->assertOk();
        $response->assertDontSee('Account access');
        $response->assertDontSee('Create user account');
    }
}
