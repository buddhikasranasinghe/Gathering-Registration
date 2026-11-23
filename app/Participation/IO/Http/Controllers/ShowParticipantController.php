<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShowParticipantController extends Controller
{
    private const TABS = ['participant', 'payment', 'ticket'];

    public function __invoke(Request $request, Participant $participant): View
    {
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        $participant->load([
            'batch:id,name',
            'payments' => fn ($query) => $query->with('recordedBy:id,name')->latest('paid_at'),
            'issuedTickets' => fn ($query) => $query->with('issuedBy:id,name')->latest('issued_at'),
        ]);

        $activeTab = $request->string('tab')->toString();

        return view('participants.show', [
            'participant' => $participant,
            'summary' => $this->summary($participant),
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

    /**
     * @return array{ticketPrice: float, totalPaid: float, remaining: float, refund: float}
     */
    private function summary(Participant $participant): array
    {
        $people = 1 + ($participant->spouse_attending ? 1 : 0);
        $ticketPrice = (float) config('tickets.price_per_person') * $people;
        $totalPaid = (float) $participant->payments->sum('amount');
        $remaining = max(0.0, $ticketPrice - $totalPaid);
        $refund = max(0.0, $totalPaid - $ticketPrice);

        return [
            'ticketPrice' => $ticketPrice,
            'totalPaid' => $totalPaid,
            'remaining' => $remaining,
            'refund' => $refund,
        ];
    }
}
