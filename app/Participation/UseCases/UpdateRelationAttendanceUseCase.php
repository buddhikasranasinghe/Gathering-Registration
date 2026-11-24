<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\AttendanceChange;
use App\Participation\UseCases\Requests\UpdateRelationAttendanceRequest;
use Illuminate\Support\Facades\DB;

final class UpdateRelationAttendanceUseCase
{
    public function handle(UpdateRelationAttendanceRequest $request): ?AttendanceChange
    {
        return DB::transaction(function () use ($request): ?AttendanceChange {
            $relation = $request->relation;
            $attendingFrom = (bool) $relation->attending;

            $relation->fill([
                'attending' => $request->attending,
                'attendance_manually_set' => true,
            ])->save();

            if ($attendingFrom === $request->attending) {
                return null;
            }

            return AttendanceChange::query()->create([
                'participant_id' => $relation->primary_participant_id,
                'participant_from' => (bool) $relation->primaryParticipant?->attending,
                'participant_to' => (bool) $relation->primaryParticipant?->attending,
                'relation_id' => $relation->getKey(),
                'relation_type' => $relation->relation?->value,
                'relation_name_from' => $relation->name,
                'relation_name_to' => $relation->name,
                'relation_from' => $attendingFrom,
                'relation_to' => $request->attending,
                'changed_by' => $request->changedBy->getKey(),
            ]);
        });
    }
}
