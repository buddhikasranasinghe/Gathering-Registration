<?php

declare(strict_types=1);

namespace App\Identity\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\IO\Http\Requests\CreateUserFromParticipantRequest;
use App\Identity\UseCases\CreateUserFromParticipantUseCase;
use App\Identity\UseCases\Requests\CreateUserFromParticipantRequest as CreateUserFromParticipant;
use App\Participation\Entities\Participant;
use Illuminate\Http\RedirectResponse;

class CreateUserFromParticipantController extends Controller
{
    public function __invoke(
        CreateUserFromParticipantRequest $request,
        Participant $participant,
        CreateUserFromParticipantUseCase $createUser,
    ): RedirectResponse {
        $user = $createUser->handle(new CreateUserFromParticipant(
            participant: $participant,
            email: $request->validated('email'),
        ));

        return back()->with('status', 'User account created for '.$user->name.'. They can sign in with '.$user->email.'.');
    }
}
