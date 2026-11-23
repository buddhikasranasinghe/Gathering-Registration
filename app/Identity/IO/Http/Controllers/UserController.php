<?php

declare(strict_types=1);

namespace App\Identity\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Batch;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $this->filters($request);

        return view('users.index', [
            'users' => $this->users($filters),
            'filters' => $filters,
            'batches' => Batch::query()->orderBy('name')->orderBy('id')->get(),
            'roles' => UserRole::cases(),
            'search' => $filters['search'],
        ]);
    }

    /**
     * @return array{search: string, role: ?UserRole, batchId: ?int}
     */
    private function filters(Request $request): array
    {
        return [
            'search' => trim((string) $request->query('search', '')),
            'role' => UserRole::tryFrom($request->string('role')->toString()),
            'batchId' => $request->integer('batch_id') ?: null,
        ];
    }

    /**
     * @param  array{search: string, role: ?UserRole, batchId: ?int}  $filters
     * @return LengthAwarePaginator<int, User>
     */
    private function users(array $filters): LengthAwarePaginator
    {
        $query = User::query()->with('batch:id,name');

        if ($filters['search'] !== '') {
            $searchTerm = '%'.$filters['search'].'%';

            $query->where(function ($query) use ($searchTerm): void {
                $query->whereLike('name', $searchTerm)
                    ->orWhereLike('email', $searchTerm);
            });
        }

        if ($filters['role'] !== null) {
            $query->where('role', $filters['role']->value);
        }

        if ($filters['batchId'] !== null) {
            $query->where('batch_id', $filters['batchId']);
        }

        return $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();
    }
}
