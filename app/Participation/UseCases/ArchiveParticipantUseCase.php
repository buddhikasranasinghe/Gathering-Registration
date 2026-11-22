<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\UseCases\Requests\ArchiveParticipantRequest;

final class ArchiveParticipantUseCase
{
    public function handle(ArchiveParticipantRequest $request): void
    {
        $request->participant->forceFill([
            'archived_at' => now(),
            'archive_note' => $request->note,
            'archived_by' => $request->archivedBy,
        ])->save();
    }
}
