<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use App\Participation\Entities\Payment;
use App\Participation\IO\Http\Requests\UpdatePaymentRequest;
use App\Participation\UseCases\Requests\UpdatePaymentRequest as UpdatePayment;
use App\Participation\UseCases\UpdatePaymentUseCase;
use Illuminate\Http\RedirectResponse;

class UpdatePaymentController extends Controller
{
    public function __invoke(
        UpdatePaymentRequest $request,
        Participant $participant,
        Payment $payment,
        UpdatePaymentUseCase $updatePayment,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        if ($payment->participant_id !== $participant->getKey()) {
            abort(404);
        }

        $updatePayment->handle(new UpdatePayment(
            payment: $payment,
            updatedBy: $user,
            amount: (float) $request->input('amount'),
            method: $request->string('method')->toString(),
            paidAt: $request->date('paid_at'),
            slip: $request->file('slip'),
            removeSlip: $request->boolean('remove_slip'),
        ));

        return back()->with('status', 'Payment updated.');
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
