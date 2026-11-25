<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Participation\Entities\Participant;
use App\Participation\UseCases\DeleteRelationUseCase;
use App\Participation\UseCases\Requests\DeleteRelationRequest as DeleteRelation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeleteRelationController extends Controller
{
    public function __invoke(
        Request $request,
        Participant $participant,
        Participant $relation,
        DeleteRelationUseCase $deleteRelation,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        if ($relation->primary_participant_id !== $participant->getKey()) {
            abort(404);
        }

        $deleteRelation->handle(new DeleteRelation(
            relation: $relation,
            changedBy: $user,
        ));

        return back()->with('status', 'Relation removed.');
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->isBatchScoped() && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
