<?php

declare(strict_types=1);

namespace App\Participation\IO\Console\Commands;

use App\Participation\Entities\IssuedTicket;
use App\Participation\Services\TicketPdfGenerator;
use App\Participation\Services\TicketStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;
use Throwable;

final class RegenerateTicketPdfsCommand extends Command
{
    protected $signature = 'tickets:regenerate-pdfs
                            {--force : Regenerate the PDF even when it already exists on the target disk}';

    protected $description = 'Regenerate issued ticket PDFs onto the configured ticket storage disk';

    public function handle(TicketPdfGenerator $pdf, TicketStorage $storage): int
    {
        $disk = $storage->disk();
        $regenerated = 0;
        $skipped = 0;
        $failed = 0;

        IssuedTicket::query()
            ->with('participant')
            ->chunkById(100, function ($tickets) use ($pdf, $storage, $disk, &$regenerated, &$skipped, &$failed): void {
                foreach ($tickets as $ticket) {
                    $path = $this->pathFor($ticket);

                    if (! $this->option('force') && $storage->exists($path, $disk)) {
                        $skipped++;
                        $this->line("Skipped {$ticket->ticket_number}: file already exists on [{$disk}].");

                        continue;
                    }

                    $participant = $ticket->participant;

                    if ($participant === null) {
                        $failed++;
                        $this->error("Failed {$ticket->ticket_number}: the participant is missing.");

                        continue;
                    }

                    try {
                        $storage->put($path, $this->generate($ticket, $pdf), $disk);

                        $ticket->forceFill([
                            'file_path' => $path,
                            'file_disk' => $disk,
                        ])->save();

                        $regenerated++;
                        $this->line("Regenerated {$ticket->ticket_number} on [{$disk}].");
                    } catch (Throwable $exception) {
                        $failed++;
                        $this->error("Failed {$ticket->ticket_number}: {$exception->getMessage()}");
                    }
                }
            });

        $this->newLine();
        $this->info("Regenerated {$regenerated} ticket(s) on [{$disk}], skipped {$skipped}, failed {$failed}.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function pathFor(IssuedTicket $ticket): string
    {
        return $ticket->file_path ?? $ticket->issued_at->year.'/'.$ticket->ticket_number.'.pdf';
    }

    private function generate(IssuedTicket $ticket, TicketPdfGenerator $pdf): string
    {
        return $pdf->generate([
            'ticketNumber' => $ticket->ticket_number,
            'name' => (string) $ticket->participant->name,
            'attendees' => array_map(
                fn (array $attendee): string => ($attendee['relation'] ?? null) === null
                    ? $attendee['name']
                    : $attendee['name'].' ('.$attendee['relation'].')',
                (array) $ticket->attendees,
            ),
            'issuedAt' => $ticket->issued_at->format('j M Y'),
            'price' => number_format((float) $ticket->ticket_price, 2),
            'verificationUrl' => URL::signedRoute('tickets.verify', ['ticket' => $ticket->ticket_number]),
        ]);
    }
}
