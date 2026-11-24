<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantRelation;
use App\Participation\IO\Http\Requests\StoreRelationRequest;
use App\Participation\UseCases\Requests\StoreRelationRequest as StoreRelation;
use App\Participation\UseCases\StoreRelationUseCase;
use Illuminate\Http\RedirectResponse;

class StoreRelationController extends Controller
{
    public function __invoke(
        StoreRelationRequest $request,
        Participant $participant,
        StoreRelationUseCase $storeRelation,
    ): RedirectResponse {
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        $storeRelation->handle(new StoreRelation(
            participant: $participant,
            changedBy: $user,
            name: $request->string('name')->toString(),
            relation: ParticipantRelation::from($request->string('relation')->toString()),
        ));

        return back()->with('status', 'Relation added.');
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
