<?php

declare(strict_types=1);

namespace App\Identity\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __invoke(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        return view('users.index', [
            'users' => $this->users($search),
            'search' => $search,
        ]);
    }

    /**
     * @return LengthAwarePaginator<int, User>
     */
    private function users(string $search): LengthAwarePaginator
    {
        $query = User::query()->with('batch:id,name');

        if ($search !== '') {
            $searchTerm = '%'.$search.'%';

            $query->where(function ($query) use ($searchTerm): void {
                $query->whereLike('name', $searchTerm)
                    ->orWhereLike('email', $searchTerm);
            });
        }

        return $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(25)
            ->withQueryString();
    }
}
