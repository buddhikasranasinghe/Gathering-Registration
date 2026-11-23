<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use App\Participation\IO\Http\Requests\IssueTicketRequest;
use App\Participation\UseCases\IssueTicketUseCase;
use App\Participation\UseCases\Requests\IssueTicketRequest as IssueTicket;
use Illuminate\Http\RedirectResponse;

class IssueTicketController extends Controller
{
    public function __invoke(
        IssueTicketRequest $request,
        Participant $participant,
        IssueTicketUseCase $issueTicket,
    ): RedirectResponse {
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        $issueTicket->handle(new IssueTicket(
            participant: $participant,
            issuedBy: $user,
        ));

        return back()->with('status', 'Ticket issued.');
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
