<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use App\Participation\Entities\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamPaymentSlipPreviewController extends Controller
{
    public function __invoke(Request $request, Participant $participant, Payment $payment): StreamedResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }

        if ($payment->participant_id !== $participant->getKey() || $payment->slip_path === null || $payment->slipPreviewKind() !== 'pdf') {
            abort(404);
        }

        if (! Storage::disk()->exists($payment->slip_path)) {
            abort(404);
        }

        $filename = str_replace(['"', "\r", "\n"], '', basename($payment->slip_path));

        return Storage::disk()->response($payment->slip_path, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
