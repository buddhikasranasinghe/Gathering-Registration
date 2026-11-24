<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Identity\Entities\User;
use App\Participation\Entities\Batch;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantRelation;
use App\Participation\Entities\Payment;
use App\Participation\IO\Mail\TicketMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

final class TicketGenerationTest extends TestCase
{
    #[Test]
    public function it_generates_emails_and_stores_a_ticket_for_a_fully_paid_participant(): void
    {
        Storage::fake('local');
        Mail::fake();
        config()->set('tickets.storage_disk', 'local');
        config()->set('tickets.price_per_person', 4000);
        $this->travelTo('2026-11-24 10:00:00');

        $admin = User::factory()->superAdmin()->create(['name' => 'Alice Admin']);
        $participant = Participant::factory()->create([
            'name' => 'Anita Perera',
            'email' => 'anita@example.test',
            'attending' => true,
        ]);
        Participant::factory()
            ->relationTo($participant, ParticipantRelation::Spouse)
            ->create(['name' => 'Rohan Perera']);
        Payment::factory()->for($participant)->create(['amount' => '8000.00']);

        $response = $this->actingAs($admin)->post(
            route('participants.tickets.store', $participant),
            ['email' => 'override@example.test'],
        );

        $response->assertRedirect();
        $response->assertSessionHas('status', 'Ticket generated and emailed to override@example.test.');

        $ticket = IssuedTicket::query()->sole();

        $this->assertSame('GATH-2026-00001', $ticket->ticket_number);
        $this->assertSame('active', $ticket->status->value);
        $this->assertSame(8000.0, (float) $ticket->ticket_price);
        $this->assertSame([
            ['name' => 'Anita Perera', 'relation' => null],
            ['name' => 'Rohan Perera', 'relation' => 'spouse'],
        ], $ticket->attendees);
        $this->assertSame($admin->getKey(), $ticket->issued_by);
        $this->assertSame('Alice Admin', $ticket->issued_by_name);
        $this->assertSame('local', $ticket->file_disk);
        $this->assertSame('2026/GATH-2026-00001.pdf', $ticket->file_path);
        $this->assertSame('override@example.test', $ticket->emailed_to);
        $this->assertSame(1, $ticket->email_send_count);
        $this->assertNotNull($ticket->emailed_at);

        Storage::disk('local')->assertExists($ticket->file_path);
        $this->assertStringStartsWith('%PDF', (string) Storage::disk('local')->get($ticket->file_path));

        Mail::assertSent(TicketMail::class, function (TicketMail $mail) use ($ticket): bool {
            return $mail->hasTo('override@example.test')
                && count($mail->attachments()) === 1
                && $mail->attachments()[0]->as === $ticket->ticket_number.'.pdf';
        });
    }

    #[Test]
    public function it_generates_a_ticket_without_sending_when_no_recipient_is_available(): void
    {
        Storage::fake('local');
        Mail::fake();
        config()->set('tickets.storage_disk', 'local');
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create([
            'email' => null,
            'attending' => true,
        ]);
        Payment::factory()->for($participant)->create(['amount' => '4000.00']);

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.store', $participant),
        )->assertSessionHas('status', 'Ticket generated. No email address is recorded; add one before sending.');

        $this->assertSame(1, IssuedTicket::query()->count());
        Mail::assertNothingSent();
    }

    #[Test]
    public function it_prices_attending_children_at_the_configured_child_rate(): void
    {
        Storage::fake('local');
        Mail::fake();
        config()->set('tickets.storage_disk', 'local');
        config()->set('tickets.price_per_person', 4000);
        config()->set('tickets.price_per_child', 0);

        $participant = Participant::factory()->create([
            'email' => 'anita@example.test',
            'attending' => true,
        ]);
        Participant::factory()
            ->relationTo($participant, ParticipantRelation::Spouse)
            ->create(['name' => 'Rohan Perera']);
        Participant::factory()
            ->relationTo($participant, ParticipantRelation::Child)
            ->create(['name' => 'Maya Perera']);
        Payment::factory()->for($participant)->create(['amount' => '8000.00']);

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.store', $participant),
        )->assertSessionHas('status', 'Ticket generated and emailed to anita@example.test.');

        $ticket = IssuedTicket::query()->sole();

        $this->assertSame(8000.0, (float) $ticket->ticket_price);
        $this->assertSame([
            ['name' => $participant->name, 'relation' => null],
            ['name' => 'Rohan Perera', 'relation' => 'spouse'],
            ['name' => 'Maya Perera', 'relation' => 'child'],
        ], $ticket->attendees);
    }

    #[Test]
    public function it_rejects_an_invalid_email_before_generating_a_ticket(): void
    {
        $participant = Participant::factory()->create(['attending' => true]);
        Payment::factory()->for($participant)->create(['amount' => '4000.00']);

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.store', $participant),
            ['email' => 'not-an-email'],
        )->assertSessionHasErrors('email');

        $this->assertSame(0, IssuedTicket::query()->count());
    }

    #[Test]
    public function it_refuses_to_generate_a_ticket_while_payment_is_incomplete(): void
    {
        Storage::fake('local');
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['attending' => true]);
        Payment::factory()->for($participant)->create(['amount' => '1500.00']);

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.store', $participant),
        )->assertSessionHasErrors('ticket');

        $this->assertSame(0, IssuedTicket::query()->count());
    }

    #[Test]
    public function it_refuses_to_generate_a_ticket_when_nobody_is_attending(): void
    {
        $participant = Participant::factory()->create(['attending' => false]);

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.store', $participant),
        )->assertSessionHasErrors('ticket');

        $this->assertSame(0, IssuedTicket::query()->count());
    }

    #[Test]
    public function it_refuses_to_generate_a_second_active_ticket(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['attending' => true]);
        Payment::factory()->for($participant)->create(['amount' => '4000.00']);
        $existingTicket = IssuedTicket::factory()->for($participant)->create();

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.store', $participant),
        )->assertSessionHasErrors('ticket');

        $this->assertSame(1, IssuedTicket::query()->count());
        $this->assertSame($existingTicket->getKey(), IssuedTicket::query()->sole()->getKey());
    }

    #[Test]
    public function it_allows_a_new_ticket_after_the_previous_ticket_was_deactivated(): void
    {
        Storage::fake('local');
        Mail::fake();
        config()->set('tickets.storage_disk', 'local');
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['attending' => true, 'email' => null]);
        Payment::factory()->for($participant)->create(['amount' => '4000.00']);
        $deactivatedTicket = IssuedTicket::factory()->for($participant)->deactivated()->create();

        $this->actingAs(User::factory()->superAdmin()->create())->post(
            route('participants.tickets.store', $participant),
        )->assertRedirect();

        $this->assertSame(2, IssuedTicket::query()->count());
        $this->assertSame(1, IssuedTicket::query()->where('status', 'active')->count());
        $this->assertSame('deactivated', $deactivatedTicket->fresh()->status->value);
    }

    #[Test]
    public function it_generates_sequential_ticket_numbers_for_each_participant(): void
    {
        Storage::fake('local');
        config()->set('tickets.storage_disk', 'local');
        config()->set('tickets.price_per_person', 4000);
        $this->travelTo('2026-11-24 10:00:00');

        $first = Participant::factory()->create(['attending' => true]);
        Payment::factory()->for($first)->create(['amount' => '4000.00']);
        $second = Participant::factory()->create(['attending' => true]);
        Payment::factory()->for($second)->create(['amount' => '4000.00']);

        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('participants.tickets.store', $first))->assertRedirect();
        $this->actingAs($admin)->post(route('participants.tickets.store', $second))->assertRedirect();

        $this->assertEqualsCanonicalizing(
            ['GATH-2026-00001', 'GATH-2026-00002'],
            IssuedTicket::query()->pluck('ticket_number')->all(),
        );
    }

    #[Test]
    public function an_unprivileged_user_cannot_generate_a_ticket(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $participant = Participant::factory()->create(['attending' => true]);
        Payment::factory()->for($participant)->create(['amount' => '4000.00']);

        $this->actingAs(User::factory()->create())->post(
            route('participants.tickets.store', $participant),
        )->assertForbidden();

        $this->assertSame(0, IssuedTicket::query()->count());
    }

    #[Test]
    public function an_admin_cannot_generate_a_ticket_outside_their_batch(): void
    {
        config()->set('tickets.price_per_person', 4000);

        $admin = User::factory()->admin()->create(['batch_id' => Batch::factory()]);
        $participant = Participant::factory()->for(Batch::factory())->create(['attending' => true]);
        Payment::factory()->for($participant)->create(['amount' => '4000.00']);

        $this->actingAs($admin)->post(
            route('participants.tickets.store', $participant),
        )->assertNotFound();

        $this->assertSame(0, IssuedTicket::query()->count());
    }

    #[Test]
    public function a_guest_is_redirected_to_login_when_generating_a_ticket(): void
    {
        $participant = Participant::factory()->create();

        $this->post(route('participants.tickets.store', $participant))
            ->assertRedirect(route('login'));
    }
}
