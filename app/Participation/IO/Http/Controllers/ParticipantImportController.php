<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Participation\Exceptions\ParticipantImportAlreadyRunningException;
use App\Participation\Exceptions\ParticipantImportException;
use App\Participation\IO\Http\Requests\ImportParticipantsRequest;
use App\Participation\UseCases\ImportUploadedParticipantsUseCase;
use Illuminate\Http\RedirectResponse;

class ParticipantImportController extends Controller
{
    public function store(
        ImportParticipantsRequest $request,
        ImportUploadedParticipantsUseCase $import,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        try {
            $run = $import->handle($user, $request->file('file'));
        } catch (ParticipantImportAlreadyRunningException|ParticipantImportException $exception) {
            return back()->withErrors(['file' => $exception->getMessage()]);
        }

        return back()->with('status', 'Participant import completed: '.$run->summary().'.');
    }
}
