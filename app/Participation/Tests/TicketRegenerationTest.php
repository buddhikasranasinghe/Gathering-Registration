<?php

declare(strict_types=1);

namespace App\Participation\Tests;

use App\Foundation\Tests\TestCase;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\Entities\TicketStatus;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;

final class TicketRegenerationTest extends TestCase
{
    #[Test]
    public function it_regenerates_missing_ticket_pdfs_onto_the_configured_disk(): void
    {
        Storage::fake('private');
        config()->set('tickets.storage_disk', 'private');

        $ticket = $this->ticketWithMissingFile();

        $this->artisan('tickets:regenerate-pdfs')
            ->expectsOutputToContain('Regenerated 1 ticket(s) on [private]')
            ->assertSuccessful();

        Storage::disk('private')->assertExists('2026/GATH-2026-00001.pdf');
        $this->assertStringStartsWith('%PDF', Storage::disk('private')->get('2026/GATH-2026-00001.pdf'));

        $ticket->refresh();

        $this->assertSame('private', $ticket->file_disk);
        $this->assertSame('2026/GATH-2026-00001.pdf', $ticket->file_path);
        $this->assertSame('GATH-2026-00001', $ticket->ticket_number);
        $this->assertSame(TicketStatus::Active, $ticket->status);
    }

    #[Test]
    public function it_skips_tickets_whose_pdf_already_exists_on_the_target_disk(): void
    {
        Storage::fake('private');
        config()->set('tickets.storage_disk', 'private');

        $ticket = $this->ticketWithMissingFile();
        Storage::disk('private')->put($ticket->file_path, 'existing contents');

        $this->artisan('tickets:regenerate-pdfs')
            ->expectsOutputToContain('skipped 1')
            ->assertSuccessful();

        $this->assertSame('existing contents', Storage::disk('private')->get($ticket->file_path));
    }

    #[Test]
    public function the_force_option_regenerates_pdfs_that_already_exist(): void
    {
        Storage::fake('private');
        config()->set('tickets.storage_disk', 'private');

        $ticket = $this->ticketWithMissingFile();
        Storage::disk('private')->put($ticket->file_path, 'existing contents');

        $this->artisan('tickets:regenerate-pdfs', ['--force' => true])
            ->expectsOutputToContain('Regenerated 1 ticket(s) on [private]')
            ->assertSuccessful();

        $this->assertStringStartsWith('%PDF', Storage::disk('private')->get($ticket->file_path));
    }

    private function ticketWithMissingFile(): IssuedTicket
    {
        $participant = Participant::factory()->create(['name' => 'Anita Perera']);

        return IssuedTicket::factory()->for($participant)->create([
            'ticket_number' => 'GATH-2026-00001',
            'issued_at' => '2026-11-24 10:00:00',
            'file_path' => '2026/GATH-2026-00001.pdf',
            'file_disk' => 'local',
            'ticket_price' => 4000,
            'attendees' => [
                ['name' => 'Anita Perera', 'relation' => null],
                ['name' => 'Sunil Perera', 'relation' => 'Spouse'],
            ],
        ]);
    }
}
