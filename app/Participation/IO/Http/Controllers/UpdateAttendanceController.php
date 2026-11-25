<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Participation\Entities\Participant;
use App\Participation\IO\Http\Requests\UpdateAttendanceRequest;
use App\Participation\UseCases\Requests\UpdateAttendanceRequest as UpdateAttendance;
use App\Participation\UseCases\UpdateAttendanceUseCase;
use Illuminate\Http\RedirectResponse;

class UpdateAttendanceController extends Controller
{
    public function __invoke(
        UpdateAttendanceRequest $request,
        Participant $participant,
        UpdateAttendanceUseCase $updateAttendance,
    ): RedirectResponse {
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        $updateAttendance->handle(new UpdateAttendance(
            participant: $participant,
            changedBy: $user,
            attending: $request->boolean('attending'),
        ));

        return back()->with('status', 'Attendance updated.');
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->isBatchScoped() && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
