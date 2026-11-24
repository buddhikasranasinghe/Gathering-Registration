<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Participation\Entities\Batch;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

final class TicketDeactivationTest extends TestCase
{
    #[Test]
    public function it_deactivates_a_ticket_without_deleting_its_file(): void
    {
        Storage::fake('local');
        config()->set('tickets.storage_disk', 'local');

        $admin = User::factory()->superAdmin()->create(['name' => 'Alice Admin']);
        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->withFile()->create();
        Storage::disk('local')->put($ticket->file_path, '%PDF-1.4 ticket contents');

        $response = $this->actingAs($admin)->patch(
            route('participants.tickets.deactivate', [$participant, $ticket]),
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Ticket deactivated.');

        $ticket->refresh();
        $this->assertSame('deactivated', $ticket->status->value);
        $this->assertNotNull($ticket->deactivated_at);
        $this->assertSame($admin->getKey(), $ticket->deactivated_by);
        $this->assertSame('Alice Admin', $ticket->deactivated_by_name);
        Storage::disk('local')->assertExists($ticket->file_path);
    }

    #[Test]
    public function it_refuses_to_deactivate_a_ticket_that_is_already_deactivated(): void
    {
        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->deactivated()->create();

        $this->actingAs(User::factory()->superAdmin()->create())->patch(
            route('participants.tickets.deactivate', [$participant, $ticket]),
        )->assertSessionHasErrors('ticket');

        $this->assertSame('deactivated', $ticket->fresh()->status->value);
    }

    #[Test]
    public function a_ticket_cannot_be_deactivated_through_another_participant(): void
    {
        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for(Participant::factory())->create();

        $this->actingAs(User::factory()->superAdmin()->create())->patch(
            route('participants.tickets.deactivate', [$participant, $ticket]),
        )->assertNotFound();

        $this->assertSame('active', $ticket->fresh()->status->value);
    }

    #[Test]
    public function an_admin_cannot_deactivate_a_ticket_outside_their_batch(): void
    {
        $admin = User::factory()->admin()->create(['batch_id' => Batch::factory()]);
        $participant = Participant::factory()->for(Batch::factory())->create();
        $ticket = IssuedTicket::factory()->for($participant)->create();

        $this->actingAs($admin)->patch(
            route('participants.tickets.deactivate', [$participant, $ticket]),
        )->assertNotFound();

        $this->assertSame('active', $ticket->fresh()->status->value);
    }

    #[Test]
    public function an_unprivileged_user_cannot_deactivate_a_ticket(): void
    {
        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->create();

        $this->actingAs(User::factory()->create())->patch(
            route('participants.tickets.deactivate', [$participant, $ticket]),
        )->assertForbidden();

        $this->assertSame('active', $ticket->fresh()->status->value);
    }

    #[Test]
    public function a_guest_is_redirected_to_login_when_deactivating_a_ticket(): void
    {
        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->create();

        $this->patch(route('participants.tickets.deactivate', [$participant, $ticket]))
            ->assertRedirect(route('login'));

        $this->assertSame('active', $ticket->fresh()->status->value);
    }

    #[Test]
    public function when_an_active_ticket_is_listed_then_resend_and_deactivate_controls_are_shown(): void
    {
        $participant = Participant::factory()->create(['email' => 'anita@example.test']);
        $ticket = IssuedTicket::factory()->for($participant)->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'ticket']));

        $response->assertOk();
        $response->assertSee('data-ticket-resend', false);
        $response->assertSee('data-ticket-deactivate', false);
        $response->assertSee('data-ticket-resend-confirm', false);
        $response->assertSee('data-ticket-deactivate-confirm', false);
        $response->assertSee($ticket->ticket_number);
        $response->assertSee('Deactivate');
        $response->assertSee('Resend');
    }

    #[Test]
    public function when_a_ticket_is_deactivated_then_its_actions_are_hidden(): void
    {
        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->deactivated()->create();

        $response = $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('participants.show', [$participant, 'tab' => 'ticket']));

        $response->assertOk();
        $response->assertSee('Deactivated');
        $response->assertDontSee('data-ticket-resend'.PHP_EOL, false);
        $response->assertDontSee('data-ticket-deactivate'.PHP_EOL, false);
        $response->assertSee('data-ticket-deactivate-confirm', false);
    }
}
