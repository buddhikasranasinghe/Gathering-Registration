<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use App\Participation\Entities\Payment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadPaymentSlipController extends Controller
{
    public function __invoke(Request $request, Participant $participant, Payment $payment): StreamedResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }

        if ($payment->participant_id !== $participant->getKey() || $payment->slip_path === null) {
            abort(404);
        }

        return response()->download(storage_path('app/private/'.$payment->slip_path), basename($payment->slip_path));
    }
}
