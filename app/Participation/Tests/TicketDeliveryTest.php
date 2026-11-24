<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Participation\Entities\Batch;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\IO\Mail\TicketMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

final class TicketDeliveryTest extends TestCase
{
    #[Test]
    public function it_resends_a_ticket_to_an_override_recipient_and_records_delivery(): void
    {
        Storage::fake('local');
        Mail::fake();
        config()->set('tickets.storage_disk', 'local');

        $participant = Participant::factory()->create(['email' => 'participant@example.test']);
        $ticket = IssuedTicket::factory()->for($participant)->withFile()->create();
        Storage::disk('local')->put($ticket->file_path, '%PDF-1.4 ticket contents');

        $response = $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.resend', [$participant, $ticket]),
            ['email' => 'override@example.test'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Ticket emailed to override@example.test.');

        Mail::assertSent(TicketMail::class, function (TicketMail $mail) use ($ticket): bool {
            return $mail->hasTo('override@example.test')
                && count($mail->attachments()) === 1
                && $mail->attachments()[0]->as === $ticket->ticket_number.'.pdf';
        });

        $ticket->refresh();
        $this->assertSame('override@example.test', $ticket->emailed_to);
        $this->assertSame(1, $ticket->email_send_count);
        $this->assertNotNull($ticket->emailed_at);
    }

    #[Test]
    public function the_ticket_mail_contains_the_ticket_number_and_attendee_name(): void
    {
        $participant = Participant::factory()->create(['name' => 'Anita Perera']);
        $ticket = IssuedTicket::factory()->for($participant)->create([
            'ticket_number' => 'GATH-2026-00001',
        ]);

        $mail = new TicketMail($ticket, 'anita@example.test');

        $mail->assertSeeInHtml('GATH-2026-00001');
        $mail->assertSeeInText('Anita Perera');
    }

    #[Test]
    public function it_refuses_to_resend_a_deactivated_ticket(): void
    {
        Storage::fake('local');
        Mail::fake();
        config()->set('tickets.storage_disk', 'local');

        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->withFile()->deactivated()->create();
        Storage::disk('local')->put($ticket->file_path, '%PDF-1.4 ticket contents');

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.resend', [$participant, $ticket]),
            ['email' => 'anita@example.test'],
        )->assertSessionHasErrors('ticket');

        Mail::assertNothingSent();
        $this->assertSame(0, $ticket->fresh()->email_send_count);
    }

    #[Test]
    public function it_refuses_to_resend_when_the_ticket_file_is_missing(): void
    {
        Storage::fake('local');
        Mail::fake();
        config()->set('tickets.storage_disk', 'local');

        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->withFile()->create();

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.resend', [$participant, $ticket]),
            ['email' => 'anita@example.test'],
        )->assertSessionHasErrors('ticket');

        Mail::assertNothingSent();
        $this->assertSame(0, $ticket->fresh()->email_send_count);
    }

    #[Test]
    public function it_requires_a_valid_recipient_email(): void
    {
        Storage::fake('local');
        Mail::fake();
        config()->set('tickets.storage_disk', 'local');

        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->withFile()->create();
        Storage::disk('local')->put($ticket->file_path, '%PDF-1.4 ticket contents');

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.resend', [$participant, $ticket]),
            ['email' => 'not-an-email'],
        )->assertSessionHasErrors('email');

        Mail::assertNothingSent();
    }

    #[Test]
    public function a_ticket_cannot_be_resent_through_another_participant(): void
    {
        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for(Participant::factory())->withFile()->create();

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.resend', [$participant, $ticket]),
            ['email' => 'anita@example.test'],
        )->assertNotFound();
    }

    #[Test]
    public function an_admin_cannot_resend_a_ticket_outside_their_batch(): void
    {
        $admin = User::factory()->admin()->create(['batch_id' => Batch::factory()]);
        $participant = Participant::factory()->for(Batch::factory())->create();
        $ticket = IssuedTicket::factory()->for($participant)->withFile()->create();

        $this->actingAs($admin)->post(
            route('participants.tickets.resend', [$participant, $ticket]),
            ['email' => 'anita@example.test'],
        )->assertNotFound();
    }

    #[Test]
    public function an_unprivileged_user_cannot_resend_a_ticket(): void
    {
        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->withFile()->create();

        $this->actingAs(User::factory()->create())->post(
            route('participants.tickets.resend', [$participant, $ticket]),
            ['email' => 'anita@example.test'],
        )->assertForbidden();
    }

    #[Test]
    public function a_guest_is_redirected_to_login_when_resending_a_ticket(): void
    {
        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->withFile()->create();

        $this->post(
            route('participants.tickets.resend', [$participant, $ticket]),
            ['email' => 'anita@example.test'],
        )->assertRedirect(route('login'));
    }

    #[Test]
    public function it_downloads_the_ticket_from_the_configured_storage_disk(): void
    {
        Storage::fake('local');
        config()->set('tickets.storage_disk', 'local');

        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->withFile()->create();
        Storage::disk('local')->put($ticket->file_path, '%PDF-1.4 ticket contents');

        $response = $this->actingAs(User::factory()->superAdmin()->create())->get(
            route('participants.tickets.download', [$participant, $ticket]),
        );

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $response->assertStreamedContent('%PDF-1.4 ticket contents');
    }

    #[Test]
    public function it_returns_not_found_when_the_downloaded_ticket_file_is_missing(): void
    {
        Storage::fake('local');
        config()->set('tickets.storage_disk', 'local');

        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for($participant)->withFile()->create();

        $this->actingAs(User::factory()->superAdmin()->create())->get(
            route('participants.tickets.download', [$participant, $ticket]),
        )->assertNotFound();
    }

    #[Test]
    public function a_ticket_cannot_be_downloaded_through_another_participant(): void
    {
        Storage::fake('local');
        config()->set('tickets.storage_disk', 'local');

        $participant = Participant::factory()->create();
        $ticket = IssuedTicket::factory()->for(Participant::factory())->withFile()->create();
        Storage::disk('local')->put($ticket->file_path, '%PDF-1.4 ticket contents');

        $this->actingAs(User::factory()->superAdmin()->create())->get(
            route('participants.tickets.download', [$participant, $ticket]),
        )->assertNotFound();
    }
}
