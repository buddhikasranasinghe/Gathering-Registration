<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use App\Participation\Entities\Payment;
use App\Participation\UseCases\DeletePaymentUseCase;
use App\Participation\UseCases\Requests\DeletePaymentRequest as DeletePayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeletePaymentController extends Controller
{
    public function __invoke(
        Request $request,
        Participant $participant,
        Payment $payment,
        DeletePaymentUseCase $deletePayment,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();

        $this->authorizeAccess($user, $participant);

        if ($payment->participant_id !== $participant->getKey()) {
            abort(404);
        }

        $deletePayment->handle(new DeletePayment(payment: $payment));

        return back()->with('status', 'Payment deleted.');
    }

    private function authorizeAccess(User $user, Participant $participant): void
    {
        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }
    }
}
