<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Participation\Entities\IssuedTicket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VerifyTicketController extends Controller
{
    public function __invoke(Request $request, string $ticket): View
    {
        $issuedTicket = IssuedTicket::query()
            ->where('ticket_number', $ticket)
            ->firstOrFail();

        return view('tickets.verify', [
            'ticket' => $issuedTicket,
            'valid' => $issuedTicket->isActive(),
        ]);
    }
}
