<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\UserRole;
use App\Participation\Entities\Participant;
use App\Participation\Entities\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadPaymentSlipController extends Controller
{
    public function __invoke(Request $request, Participant $participant, Payment $payment): RedirectResponse
    {
        $user = $request->user();

        if ($user->role === UserRole::Admin && $participant->batch_id !== $user->batch_id) {
            abort(404);
        }

        if ($payment->participant_id !== $participant->getKey() || $payment->slip_path === null) {
            abort(404);
        }

        $options = [];

        if ($request->boolean('download')) {
            $filename = str_replace(['"', "\r", "\n"], '', basename($payment->slip_path));
            $options['ResponseContentDisposition'] = 'attachment; filename="'.$filename.'"';
        }

        return redirect()->away(
            Storage::disk()->temporaryUrl($payment->slip_path, now()->addMinutes(10), $options)
        );
    }
}
