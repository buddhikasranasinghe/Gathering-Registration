<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\AttendanceChange;
use App\Participation\Entities\Participant;
use App\Participation\UseCases\Requests\StoreRelationRequest;
use Illuminate\Support\Facades\DB;

final class StoreRelationUseCase
{
    public function handle(StoreRelationRequest $request): AttendanceChange
    {
        return DB::transaction(function () use ($request): AttendanceChange {
            $participant = $request->participant;

            $relation = Participant::query()->create([
                'name' => $request->name,
                'batch_id' => $participant->batch_id,
                'attending' => true,
                'attendance_manually_set' => true,
                'primary_participant_id' => $participant->getKey(),
                'relation' => $request->relation,
            ]);

            return AttendanceChange::query()->create([
                'participant_id' => $participant->getKey(),
                'participant_from' => (bool) $participant->attending,
                'participant_to' => (bool) $participant->attending,
                'relation_id' => $relation->getKey(),
                'relation_type' => $request->relation->value,
                'relation_name_to' => $relation->name,
                'relation_to' => true,
                'changed_by' => $request->changedBy->getKey(),
            ]);
        });
    }
}
