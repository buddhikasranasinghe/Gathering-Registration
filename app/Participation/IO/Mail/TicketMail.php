<?php

declare(strict_types=1);

namespace App\Participation\IO\Mail;

use App\Participation\Entities\IssuedTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly IssuedTicket $ticket,
        public readonly string $recipient,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your gathering ticket '.$this->ticket->ticket_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.ticket',
            with: [
                'ticketNumber' => $this->ticket->ticket_number,
                'attendeeName' => $this->ticket->participant?->name ?? 'there',
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromStorageDisk(
                $this->ticket->file_disk ?? config('tickets.storage_disk'),
                (string) $this->ticket->file_path,
            )
                ->as($this->ticket->ticket_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
