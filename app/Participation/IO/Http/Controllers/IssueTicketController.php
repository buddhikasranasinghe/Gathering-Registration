<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Participation\Entities\Participant;
use App\Participation\IO\Http\Requests\IssueTicketRequest;
use App\Participation\UseCases\IssueTicketUseCase;
use App\Participation\UseCases\Requests\IssueTicketRequest as IssueTicket;
use App\Participation\UseCases\Requests\ResendTicketRequest as ResendTicket;
use App\Participation\UseCases\ResendTicketUseCase;
use Illuminate\Http\RedirectResponse;
use Throwable;

class IssueTicketController extends Controller
{
    public function __invoke(
        IssueTicketRequest $request,
        Participant $participant,
        IssueTicketUseCase $issueTicket,
        ResendTicketUseCase $resendTicket,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        $ticket = $issueTicket->handle(new IssueTicket(
            participant: $participant,
            issuedBy: $user,
        ));

        $recipient = $request->validated('email') ?: $participant->email;

        if ($recipient === null) {
            return back()->with('status', 'Ticket generated. No email address is recorded; add one before sending.');
        }

        try {
            $resendTicket->handle(new ResendTicket(ticket: $ticket, recipient: $recipient));

            return back()->with('status', 'Ticket generated and emailed to '.$recipient.'.');
        } catch (Throwable $exception) {
            report($exception);

            return back()->with('status', 'Ticket generated, but the email could not be sent. Use Resend to try again.');
        }
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->isBatchScoped() && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
