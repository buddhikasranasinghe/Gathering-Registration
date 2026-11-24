<?php

declare(strict_types=1);

namespace App\Participation\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class TicketPdfGenerator
{
    public function __construct(private TicketQrCodeGenerator $qrCodes) {}

    /**
     * @param  array{ticketNumber: string, name: string, attendees: array<int, string>, issuedAt: string, price: string, verificationUrl: string}  $ticket
     */
    public function generate(array $ticket): string
    {
        $templatePath = (string) config('tickets.template_path');

        if (! File::exists($templatePath)) {
            throw new RuntimeException('The ticket template is missing.');
        }

        $template = base64_encode((string) File::get($templatePath));
        $qrCode = base64_encode($this->qrCodes->generate($ticket['verificationUrl']));

        $html = view('tickets.pdf', [
            'ticketNumber' => $ticket['ticketNumber'],
            'name' => $ticket['name'],
            'attendees' => $ticket['attendees'],
            'issuedAt' => $ticket['issuedAt'],
            'price' => $ticket['price'],
            'templateImage' => 'data:image/jpeg;base64,'.$template,
            'qrImage' => 'data:image/png;base64,'.$qrCode,
        ])->render();

        return Pdf::loadHTML($html)
            ->setPaper([0, 0, 720, 233], 'portrait')
            ->output();
    }
}
