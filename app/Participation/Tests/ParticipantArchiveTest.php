<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use PHPUnit\Framework\Attributes\Test;

final class ParticipantArchiveTest extends TestCase
{
    #[Test]
    public function when_a_super_admin_archives_a_participant_then_the_note_and_actor_are_recorded(): void
    {
        $user = $this->userWithRole(UserRole::SuperAdmin);
        $participant = Participant::factory()->create();

        $response = $this->actingAs($user)->post(
            route('participants.archive', $participant),
            ['note' => '  Duplicate submission.  '],
        );

        $this->assertParticipantWasArchived($response, $participant, $user);
    }

    #[Test]
    public function when_an_admin_archives_a_participant_then_the_request_is_forbidden(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs($this->userWithRole(UserRole::Admin))->post(
            route('participants.archive', $participant),
            ['note' => 'Duplicate submission.'],
        );

        $this->assertArchiveWasForbidden($response, $participant);
    }

    #[Test]
    public function when_the_note_is_missing_then_the_participant_is_not_archived(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs($this->userWithRole(UserRole::SuperAdmin))->post(
            route('participants.archive', $participant),
            ['note' => ''],
        );

        $this->assertNoteWasRejected($response, $participant, 'The note field is required.');
    }

    #[Test]
    public function when_the_note_is_too_short_then_the_participant_is_not_archived(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs($this->userWithRole(UserRole::SuperAdmin))->post(
            route('participants.archive', $participant),
            ['note' => 'no'],
        );

        $this->assertNoteWasRejected($response, $participant, 'The note field must be at least 3 characters.');
    }

    #[Test]
    public function when_a_participant_tries_to_archive_then_it_is_forbidden(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->actingAs($this->userWithRole(UserRole::User))->post(
            route('participants.archive', $participant),
            ['note' => 'Duplicate submission.'],
        );

        $this->assertArchiveWasForbidden($response, $participant);
    }

    #[Test]
    public function when_a_guest_tries_to_archive_then_it_redirects_to_login(): void
    {
        $participant = Participant::factory()->create();

        $response = $this->post(route('participants.archive', $participant), [
            'note' => 'Duplicate submission.',
        ]);

        $response->assertRedirect(route('login'));
    }

    private function userWithRole(UserRole $role): User
    {
        return match ($role) {
            UserRole::SuperAdmin => User::factory()->superAdmin()->create(),
            UserRole::Admin => User::factory()->admin()->create(),
            UserRole::User => User::factory()->create(),
        };
    }

    private function assertParticipantWasArchived($response, Participant $participant, User $user): void
    {
        $response->assertRedirect();
        $participant->refresh();

        $this->assertNotNull($participant->archived_at);
        $this->assertSame('Duplicate submission.', $participant->archive_note);
        $this->assertSame($user->getKey(), $participant->archived_by);
    }

    private function assertNoteWasRejected($response, Participant $participant, string $message): void
    {
        $response->assertSessionHasErrors(['note' => $message]);
        $participant->refresh();

        $this->assertNull($participant->archived_at);
        $this->assertNull($participant->archive_note);
    }

    private function assertArchiveWasForbidden($response, Participant $participant): void
    {
        $response->assertForbidden();
        $participant->refresh();

        $this->assertNull($participant->archived_at);
    }
}
