<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\AttendanceChange;
use App\Participation\UseCases\Requests\UpdateAttendanceRequest;
use Illuminate\Support\Facades\DB;

final class UpdateAttendanceUseCase
{
    public function handle(UpdateAttendanceRequest $request): ?AttendanceChange
    {
        return DB::transaction(function () use ($request): ?AttendanceChange {
            $participant = $request->participant;
            $attendingFrom = (bool) $participant->attending;

            $participant->fill([
                'attending' => $request->attending,
                'attendance_manually_set' => true,
            ])->save();

            if ($attendingFrom === $request->attending) {
                return null;
            }

            return AttendanceChange::query()->create([
                'participant_id' => $participant->getKey(),
                'participant_from' => $attendingFrom,
                'participant_to' => $request->attending,
                'changed_by' => $request->changedBy->getKey(),
            ]);
        });
    }
}
