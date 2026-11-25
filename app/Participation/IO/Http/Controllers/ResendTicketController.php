<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Participation\Entities\IssuedTicket;
use App\Participation\Entities\Participant;
use App\Participation\IO\Http\Requests\ResendTicketRequest;
use App\Participation\UseCases\Requests\ResendTicketRequest as ResendTicket;
use App\Participation\UseCases\ResendTicketUseCase;
use Illuminate\Http\RedirectResponse;

class ResendTicketController extends Controller
{
    public function __invoke(
        ResendTicketRequest $request,
        Participant $participant,
        IssuedTicket $ticket,
        ResendTicketUseCase $resendTicket,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        if ($ticket->participant_id !== $participant->getKey()) {
            abort(404);
        }

        $recipient = (string) $request->validated('email');

        $resendTicket->handle(new ResendTicket(ticket: $ticket, recipient: $recipient));

        return back()->with('status', 'Ticket emailed to '.$recipient.'.');
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->isBatchScoped() && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
