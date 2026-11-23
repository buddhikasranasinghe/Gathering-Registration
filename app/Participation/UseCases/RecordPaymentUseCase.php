<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\Payment;
use App\Participation\UseCases\Requests\RecordPaymentRequest;
use Illuminate\Support\Facades\DB;

final class RecordPaymentUseCase
{
    public function handle(RecordPaymentRequest $request): Payment
    {
        return DB::transaction(function () use ($request): Payment {
            $slipPath = null;

            if ($request->slip !== null) {
                $slipPath = $request->slip->store('payment-slips');
            }

            return Payment::query()->create([
                'participant_id' => $request->participant->getKey(),
                'amount' => $request->amount,
                'method' => $request->method,
                'paid_at' => $request->paidAt,
                'slip_path' => $slipPath,
                'note' => $request->note,
                'recorded_by' => $request->recordedBy->getKey(),
            ]);
        });
    }
}
