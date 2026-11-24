<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\AttendanceChange;
use App\Participation\UseCases\Requests\DeleteRelationRequest;
use Illuminate\Support\Facades\DB;

final class DeleteRelationUseCase
{
    public function handle(DeleteRelationRequest $request): AttendanceChange
    {
        return DB::transaction(function () use ($request): AttendanceChange {
            $relation = $request->relation;
            $primaryId = $relation->primary_participant_id;

            $change = AttendanceChange::query()->create([
                'participant_id' => $primaryId,
                'participant_from' => (bool) $relation->primaryParticipant?->attending,
                'participant_to' => (bool) $relation->primaryParticipant?->attending,
                'relation_id' => $relation->getKey(),
                'relation_type' => $relation->relation?->value,
                'relation_name_from' => $relation->name,
                'relation_from' => (bool) $relation->attending,
                'changed_by' => $request->changedBy->getKey(),
            ]);

            $relation->delete();

            return $change;
        });
    }
}
