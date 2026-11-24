<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\TicketStatus;
use App\Participation\Services\TicketEligibilityService;
use App\Participation\Services\TicketNumberGenerator;
use App\Participation\Services\TicketPdfGenerator;
use App\Participation\Services\TicketStorage;
use App\Participation\UseCases\Requests\IssueTicketRequest;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Throwable;

final class IssueTicketUseCase
{
    public function __construct(
        private TicketEligibilityService $eligibility,
        private TicketNumberGenerator $numbers,
        private TicketPdfGenerator $pdf,
        private TicketStorage $storage,
    ) {}

    public function handle(IssueTicketRequest $request): IssuedTicket
    {
        $participant = $request->participant;
        $assessment = $this->eligibility->assess($participant);

        if (! $assessment['canGenerate']) {
            throw ValidationException::withMessages(['ticket' => $assessment['message']]);
        }

        $year = now()->year;
        $ticketNumber = $this->numbers->next($year);
        $disk = $this->storage->disk();
        $path = $year.'/'.$ticketNumber.'.pdf';

        $pdf = $this->pdf->generate([
            'ticketNumber' => $ticketNumber,
            'name' => $participant->name,
            'attendees' => array_map(
                fn (array $attendee): string => $attendee['relation'] === null
                    ? $attendee['name']
                    : $attendee['name'].' ('.$attendee['relation'].')',
                $assessment['attendees'],
            ),
            'issuedAt' => now()->format('j M Y'),
            'price' => number_format($assessment['ticketPrice'], 2),
            'verificationUrl' => URL::signedRoute('tickets.verify', ['ticket' => $ticketNumber]),
        ]);

        try {
            $this->storage->put($path, $pdf, $disk);

            return IssuedTicket::query()->create([
                'participant_id' => $participant->getKey(),
                'ticket_number' => $ticketNumber,
                'issued_at' => now(),
                'status' => TicketStatus::Active,
                'file_path' => $path,
                'file_disk' => $disk,
                'ticket_price' => $assessment['ticketPrice'],
                'attendees' => $assessment['attendees'],
                'issued_by' => $request->issuedBy->getKey(),
                'issued_by_name' => $request->issuedBy->name,
            ]);
        } catch (Throwable $exception) {
            $this->storage->delete($path, $disk);

            throw $exception;
        }
    }
}
