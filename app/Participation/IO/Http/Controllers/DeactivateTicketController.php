<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\UseCases\DeactivateTicketUseCase;
use App\Participation\UseCases\Requests\DeactivateTicketRequest as DeactivateTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeactivateTicketController extends Controller
{
    public function __invoke(
        Request $request,
        Participant $participant,
        IssuedTicket $ticket,
        DeactivateTicketUseCase $deactivateTicket,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        if ($ticket->participant_id !== $participant->getKey()) {
            abort(404);
        }

        $deactivateTicket->handle(new DeactivateTicket(ticket: $ticket, deactivatedBy: $user));

        return back()->with('status', 'Ticket deactivated.');
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
