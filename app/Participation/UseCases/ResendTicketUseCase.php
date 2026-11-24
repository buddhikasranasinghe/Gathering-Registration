<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\IO\Mail\TicketMail;
use App\Participation\Services\TicketStorage;
use App\Participation\UseCases\Requests\ResendTicketRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class ResendTicketUseCase
{
    public function __construct(private TicketStorage $storage) {}

    public function handle(ResendTicketRequest $request): void
    {
        $ticket = $request->ticket;

        if (! $ticket->isActive()) {
            throw ValidationException::withMessages(['ticket' => 'A deactivated ticket cannot be emailed.']);
        }

        if (! $this->storage->exists($ticket->file_path, $ticket->file_disk)) {
            throw ValidationException::withMessages(['ticket' => 'The ticket file is missing from storage.']);
        }

        Mail::to($request->recipient)->send(new TicketMail($ticket, $request->recipient));

        $ticket->increment('email_send_count', 1, [
            'emailed_at' => now(),
            'emailed_to' => $request->recipient,
        ]);
    }
}
