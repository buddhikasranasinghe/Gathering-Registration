<?php

declare(strict_types=1);

namespace App\Identity\UseCases;

use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Identity\UseCases\Requests\CreateUserFromParticipantRequest;
use App\Participation\Entities\Participant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateUserFromParticipantUseCase
{
    public function handle(CreateUserFromParticipantRequest $request): User
    {
        return DB::transaction(function () use ($request): User {
            $participant = Participant::query()
                ->whereKey($request->participant->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($participant->isArchived()) {
                throw ValidationException::withMessages([
                    'participant' => 'An archived participant cannot be given a user account.',
                ]);
            }

            if ($participant->email !== null && User::query()->where('email', $participant->email)->exists()) {
                throw ValidationException::withMessages([
                    'participant' => 'A user account already exists for this participant.',
                ]);
            }

            if (User::query()->where('email', $request->email)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'An account already exists for this email address.',
                ]);
            }

            if ($participant->email !== $request->email) {
                $participant->forceFill(['email' => $request->email])->save();
            }

            return User::query()->create([
                'name' => $participant->name,
                'email' => $request->email,
                'role' => UserRole::User,
                'batch_id' => $participant->batch_id,
            ]);
        });
    }
}
