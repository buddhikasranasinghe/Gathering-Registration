<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\TicketStatus;
use App\Participation\UseCases\Requests\IssueTicketRequest;
use Illuminate\Support\Str;

final class IssueTicketUseCase
{
    public function handle(IssueTicketRequest $request): IssuedTicket
    {
        return IssuedTicket::query()->create([
            'participant_id' => $request->participant->getKey(),
            'ticket_number' => 'TCK-'.Str::upper(Str::random(8)),
            'issued_at' => now(),
            'status' => TicketStatus::Issued,
            'file_path' => null,
            'issued_by' => $request->issuedBy->getKey(),
        ]);
    }
}
