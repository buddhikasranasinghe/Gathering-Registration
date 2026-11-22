<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Participation\Entities\Participant;
use App\Participation\IO\Http\Requests\ArchiveParticipantRequest;
use App\Participation\UseCases\ArchiveParticipantUseCase;
use App\Participation\UseCases\Requests\ArchiveParticipantRequest as ArchiveParticipant;
use Illuminate\Http\RedirectResponse;

class ParticipantArchiveController extends Controller
{
    /**
     * Archive a participant with the administrator's note.
     */
    public function store(
        ArchiveParticipantRequest $request,
        Participant $participant,
        ArchiveParticipantUseCase $archive,
    ): RedirectResponse {
        $archive->handle(new ArchiveParticipant(
            participant: $participant,
            archivedBy: (int) $request->user()->getKey(),
            note: $request->string('note')->toString(),
        ));

        return back()->with('status', 'Participant archived.');
    }
}
