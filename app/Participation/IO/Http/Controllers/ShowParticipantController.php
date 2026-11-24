<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use App\Participation\Services\TicketEligibilityService;
use App\Participation\Services\TicketPriceCalculator;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowParticipantController extends Controller
{
    private const TABS = ['participant', 'payment', 'ticket'];

    public function __invoke(
        Request $request,
        Participant $participant,
        TicketPriceCalculator $ticketPriceCalculator,
        TicketEligibilityService $ticketEligibility,
    ): View {
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        $participant->load([
            'batch:id,name',
            'relatedParticipants' => fn ($query) => $query->orderBy('name')->orderBy('id'),
            'attendanceChanges' => fn ($query) => $query->with('changedBy:id,name')->latest(),
            'payments' => fn ($query) => $query
                ->with(['recordedBy:id,name', 'updatedBy:id,name'])
                ->latest('paid_at')
                ->latest('id'),
            'issuedTickets' => fn ($query) => $query->with('issuedBy:id,name')->latest('issued_at')->latest('id'),
        ]);

        $activeTab = $request->string('tab')->toString();
        $priceBreakdown = $ticketPriceCalculator->calculate($participant);
        $assessment = $ticketEligibility->assess($participant);

        return view('participants.show', [
            'participant' => $participant,
            'summary' => [
                'ticketPrice' => $assessment['ticketPrice'],
                'totalPaid' => $assessment['totalPaid'],
                'remaining' => $assessment['remaining'],
                'refund' => $assessment['refund'],
            ],
            'priceBreakdown' => $priceBreakdown['lines'],
            'ticketEligibility' => [
                'canGenerate' => $assessment['canGenerate'],
                'message' => $assessment['message'],
            ],
            'hasIssuedTickets' => $participant->issuedTickets->isNotEmpty(),
            'canManage' => $user->role === UserRole::SuperAdmin || $user->role === UserRole::Admin,
            'activeTab' => in_array($activeTab, self::TABS, true) ? $activeTab : 'participant',
        ]);
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
