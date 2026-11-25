<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Participation\Entities\Participant;
use App\Participation\IO\Http\Requests\StorePaymentRequest;
use App\Participation\UseCases\RecordPaymentUseCase;
use App\Participation\UseCases\Requests\RecordPaymentRequest as RecordPayment;
use Illuminate\Http\RedirectResponse;

class RecordPaymentController extends Controller
{
    public function __invoke(
        StorePaymentRequest $request,
        Participant $participant,
        RecordPaymentUseCase $recordPayment,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        $recordPayment->handle(new RecordPayment(
            participant: $participant,
            recordedBy: $user,
            amount: (float) $request->input('amount'),
            method: $request->string('method')->toString(),
            paidAt: $request->date('paid_at'),
            slip: $request->file('slip'),
        ));

        return back()->with('status', 'Payment recorded.');
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->isBatchScoped() && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
