<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\TicketStatus;
use App\Participation\UseCases\Requests\DeactivateTicketRequest;
use Illuminate\Validation\ValidationException;

final class DeactivateTicketUseCase
{
    public function handle(DeactivateTicketRequest $request): void
    {
        $ticket = $request->ticket;

        if (! $ticket->isActive()) {
            throw ValidationException::withMessages(['ticket' => 'This ticket is already deactivated.']);
        }

        $ticket->update([
            'status' => TicketStatus::Deactivated,
            'deactivated_at' => now(),
            'deactivated_by' => $request->deactivatedBy->getKey(),
            'deactivated_by_name' => $request->deactivatedBy->name,
        ]);
    }
}
