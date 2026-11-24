<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantImportRun;
use App\Participation\Entities\ParticipantProgressStatus;
use App\Participation\Services\TicketEligibilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ParticipantController extends Controller
{
    public function __construct(private TicketEligibilityService $ticketEligibility) {}

    /**
     * Show the participant roster and latest spreadsheet import results.
     */
    public function __invoke(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $filters = $this->filters($request);
        $latestRun = ParticipantImportRun::query()
            ->with('requestedBy:id,name')
            ->latest('id')
            ->first();

        $participants = $this->participants($filters, $user);

        $progressStatuses = $participants->getCollection()
            ->mapWithKeys(fn (Participant $participant): array => [
                $participant->getKey() => $this->ticketEligibility->progressStatus($participant),
            ])
            ->all();

        return view('participants.index', [
            'participants' => $participants,
            'progressStatuses' => $progressStatuses,
            'filters' => $filters,
            'batches' => $this->batches($user),
            'latestRun' => $latestRun,
            'runs' => ParticipantImportRun::query()
                ->with('requestedBy:id,name')
                ->latest('id')
                ->limit(5)
                ->get(),
            'stats' => [
                'active' => $this->participantsFor($user)->whereNull('archived_at')->count(),
                'relations' => $this->participantsFor($user)
                    ->whereNull('archived_at')
                    ->whereNotNull('primary_participant_id')
                    ->where('attending', true)
                    ->count(),
            ],
            'canImport' => $user->role === UserRole::SuperAdmin,
            'hasAssignedBatch' => $user->role === UserRole::SuperAdmin || $user->batch_id !== null,
        ]);
    }

    /**
     * @return array{search: string, batchId: ?int, attendance: ?bool, status: ?ParticipantProgressStatus}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'batchId' => $request->integer('batch_id') ?: null,
            'attendance' => match ($request->string('attendance')->toString()) {
                'attending' => true,
                'not_attending' => false,
                default => null,
            },
            'status' => ParticipantProgressStatus::tryFrom($request->string('status')->toString()),
        ];
    }

    /**
     * @param  array{search: string, batchId: ?int, attendance: ?bool, status: ?ParticipantProgressStatus}  $filters
     * @return LengthAwarePaginator<int, Participant>
     */
    private function participants(array $filters, User $user): LengthAwarePaginator
    {
        $query = $this->participantsFor($user)
            ->with([
                'batch:id,name',
                'relatedParticipants:id,primary_participant_id,name,relation,attending',
                'payments:id,participant_id,amount',
                'issuedTickets:id,participant_id,status',
            ])
            ->whereNull('archived_at');

        if ($user->role === UserRole::SuperAdmin && $filters['batchId'] !== null) {
            $query->where('batch_id', $filters['batchId']);
        }

        if ($filters['attendance'] !== null) {
            $query->where('attending', $filters['attendance']);
        }

        $this->applyStatusFilter($query, $filters['status']);

        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';

            $query->where(function ($query) use ($search): void {
                $query->whereLike('name', $search)
                    ->orWhereLike('call_name', $search)
                    ->orWhereLike('phone', $search)
                    ->orWhereLike('nic', $search)
                    ->orWhereLike('email', $search);
            });
        }

        return $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();
    }

    private function applyStatusFilter(Builder $query, ?ParticipantProgressStatus $status): void
    {
        if ($status === null) {
            return;
        }

        $adultPrice = number_format((float) config('tickets.price_per_person'), 2, '.', '');
        $childPrice = number_format((float) config('tickets.price_per_child'), 2, '.', '');

        $activeTicket = "EXISTS (SELECT 1 FROM issued_tickets WHERE issued_tickets.participant_id = participants.id AND issued_tickets.status = 'active')";
        $totalPaid = 'COALESCE((SELECT SUM(payments.amount) FROM payments WHERE payments.participant_id = participants.id), 0)';
        $requiredAmount = "(CASE WHEN participants.attending = TRUE THEN {$adultPrice} ELSE 0 END) + COALESCE((SELECT SUM(CASE WHEN relations.relation = 'child' THEN {$childPrice} ELSE {$adultPrice} END) FROM participants AS relations WHERE relations.primary_participant_id = participants.id AND relations.attending = TRUE), 0)";

        $condition = match ($status) {
            ParticipantProgressStatus::TicketIssued => $activeTicket,
            ParticipantProgressStatus::NotPaid => "NOT {$activeTicket} AND {$totalPaid} <= 0",
            ParticipantProgressStatus::PaymentIncomplete => "NOT {$activeTicket} AND {$totalPaid} > 0 AND {$totalPaid} < {$requiredAmount}",
            ParticipantProgressStatus::PaymentComplete => "NOT {$activeTicket} AND {$totalPaid} >= {$requiredAmount} AND ({$requiredAmount} > 0 OR {$totalPaid} > 0)",
        };

        $query->whereRaw($condition);
    }

    /**
     * @return array<int, Batch>
     */
    private function batches(User $user): array
    {
        $query = Batch::query()
            ->orderBy('name')
            ->orderBy('id');

        if ($user->role === UserRole::Admin) {
            $query->whereKey($user->batch_id);
        }

        return $query->get()->all();
    }

    private function participantsFor(User $user): Builder
    {
        $query = Participant::query();

        if ($user->role !== UserRole::Admin) {
            return $query;
        }

        if ($user->batch_id === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where('batch_id', $user->batch_id);
    }
}
