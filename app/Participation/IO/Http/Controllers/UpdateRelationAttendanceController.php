<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use App\Participation\IO\Http\Requests\UpdateRelationAttendanceRequest;
use App\Participation\UseCases\Requests\UpdateRelationAttendanceRequest as UpdateRelationAttendance;
use App\Participation\UseCases\UpdateRelationAttendanceUseCase;
use Illuminate\Http\RedirectResponse;

class UpdateRelationAttendanceController extends Controller
{
    public function __invoke(
        UpdateRelationAttendanceRequest $request,
        Participant $participant,
        Participant $relation,
        UpdateRelationAttendanceUseCase $updateAttendance,
    ): RedirectResponse {
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        if ($relation->primary_participant_id !== $participant->getKey()) {
            abort(404);
        }

        $updateAttendance->handle(new UpdateRelationAttendance(
            relation: $relation,
            changedBy: $user,
            attending: $request->boolean('attending'),
        ));

        return back()->with('status', 'Attendance updated.');
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
