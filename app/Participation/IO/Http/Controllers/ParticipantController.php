<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantImportRun;
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
            'participants' => $this->participants($filters),
            'filters' => $filters,
            'batches' => $this->batches(),
            'latestRun' => $latestRun,
            'runs' => ParticipantImportRun::query()
                ->with('requestedBy:id,name')
                ->latest('id')
                ->limit(5)
                ->get(),
            'stats' => [
                'active' => Participant::query()->whereNull('archived_at')->count(),
                'spouses' => Participant::query()
                    ->whereNull('archived_at')
                    ->where('spouse_attending', true)
                    ->count(),
                'archived' => Participant::query()->whereNotNull('archived_at')->count(),
            ],
            'canImport' => $user->role === UserRole::SuperAdmin,
        ]);
    }

    /**
     * @return array{search: string, batch: string, archived: bool}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'batch' => trim((string) $request->query('batch', '')),
            'archived' => $request->boolean('archived'),
        ];
    }

    /**
     * @param  array{search: string, batch: string, archived: bool}  $filters
     * @return LengthAwarePaginator<int, Participant>
     */
    private function participants(array $filters): LengthAwarePaginator
    {
        $query = Participant::query()->with('archivedBy:id,name');

        if ($filters['archived']) {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        if ($filters['batch'] !== '') {
            $query->where('batch', $filters['batch']);
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
     * @return array<int, string>
     */
    private function batches(): array
    {
        return Participant::query()
            ->whereNotNull('batch')
            ->select('batch', 'batch_number')
            ->distinct()
            ->orderBy('batch_number')
            ->orderBy('batch')
            ->pluck('batch')
            ->all();
    }
}
