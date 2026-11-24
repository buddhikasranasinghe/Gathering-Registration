<?php

declare(strict_types=1);

namespace App\Participation\Services;

use BaconQrCode\Renderer\GDLibRenderer;
use BaconQrCode\Writer;

final class TicketQrCodeGenerator
{
    public function generate(string $content): string
    {
        $renderer = new GDLibRenderer((int) config('tickets.qr_size', 320));

        return (new Writer($renderer))->writeString($content);
    }
}
