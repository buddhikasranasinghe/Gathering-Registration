<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Services\TicketQrCodeGenerator;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;

final class TicketVerificationTest extends TestCase
{
    #[Test]
    public function it_shows_a_valid_result_for_an_active_ticket_without_exposing_participant_details(): void
    {
        $participant = Participant::factory()->create(['name' => 'Secret Attendee']);
        $ticket = IssuedTicket::factory()->for($participant)->create([
            'ticket_number' => 'GATH-2026-00001',
        ]);

        $response = $this->get(URL::signedRoute('tickets.verify', ['ticket' => $ticket->ticket_number]));

        $response->assertOk();
        $response->assertSee('Valid ticket');
        $response->assertSee('GATH-2026-00001');
        $response->assertDontSee('Secret Attendee');
    }

    #[Test]
    public function it_shows_a_deactivated_result_for_a_deactivated_ticket(): void
    {
        $ticket = IssuedTicket::factory()->deactivated()->create([
            'ticket_number' => 'GATH-2026-00002',
        ]);

        $response = $this->get(URL::signedRoute('tickets.verify', ['ticket' => $ticket->ticket_number]));

        $response->assertOk();
        $response->assertSee('Deactivated ticket');
        $response->assertDontSee('Valid ticket');
    }

    #[Test]
    public function it_generates_a_png_qr_code_for_the_signed_verification_url(): void
    {
        $ticket = IssuedTicket::factory()->create(['ticket_number' => 'GATH-2026-00003']);
        $url = URL::signedRoute('tickets.verify', ['ticket' => $ticket->ticket_number]);

        $png = app(TicketQrCodeGenerator::class)->generate($url);

        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $png);
        $this->get($url)->assertOk()->assertSee('Valid ticket');
    }

    #[Test]
    public function it_rejects_an_unsigned_verification_link(): void
    {
        $ticket = IssuedTicket::factory()->create();

        $this->get('/tickets/'.$ticket->ticket_number.'/verify')
            ->assertForbidden();
    }

    #[Test]
    public function it_returns_not_found_for_an_unknown_ticket_number(): void
    {
        $this->get(URL::signedRoute('tickets.verify', ['ticket' => 'GATH-2099-99999']))
            ->assertNotFound();
    }
}
