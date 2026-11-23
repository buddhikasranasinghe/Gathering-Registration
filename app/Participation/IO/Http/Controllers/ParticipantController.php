<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantImportRun;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class ParticipantController extends Controller
{
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

        return view('participants.index', [
            'participants' => $this->participants($filters, $user),
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
                'spouses' => $this->participantsFor($user)
                    ->whereNull('archived_at')
                    ->where('spouse_attending', true)
                    ->count(),
                'archived' => $this->participantsFor($user)->whereNotNull('archived_at')->count(),
            ],
            'canImport' => $user->role === UserRole::SuperAdmin,
            'canArchive' => $user->role === UserRole::SuperAdmin,
            'hasAssignedBatch' => $user->role === UserRole::SuperAdmin || $user->batch_id !== null,
        ]);
    }

    /**
     * @return array{search: string, batchId: ?int, archived: bool}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'batchId' => $request->integer('batch_id') ?: null,
            'archived' => $request->boolean('archived'),
        ];
    }

    /**
     * @param  array{search: string, batchId: ?int, archived: bool}  $filters
     * @return LengthAwarePaginator<int, Participant>
     */
    private function participants(array $filters, User $user): LengthAwarePaginator
    {
        $query = $this->participantsFor($user)->with(['archivedBy:id,name', 'batch:id,name']);

        if ($filters['archived']) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        if ($user->role === UserRole::SuperAdmin && $filters['batchId'] !== null) {
            $query->where('batch_id', $filters['batchId']);
        }

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
