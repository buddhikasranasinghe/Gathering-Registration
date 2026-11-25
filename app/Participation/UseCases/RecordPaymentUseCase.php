<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\Payment;
use App\Participation\Services\PaymentSlipStorage;
use App\Participation\UseCases\Requests\RecordPaymentRequest;
use Illuminate\Support\Facades\DB;
use Throwable;

final class RecordPaymentUseCase
{
    public function __construct(private PaymentSlipStorage $slips) {}

    /**
     * @throws Throwable
     */
    public function handle(RecordPaymentRequest $request): Payment
    {
        $slipPath = $request->slip !== null ? $this->slips->store($request->slip) : null;

        try {
            return DB::transaction(fn (): Payment => Payment::query()->create([
                'participant_id' => $request->participant->getKey(),
                'amount' => $request->amount,
                'method' => $request->method,
                'paid_at' => $request->paidAt,
                'slip_path' => $slipPath,
                'recorded_by' => $request->recordedBy->getKey(),
                'recorded_by_name' => $request->recordedBy->name,
            ]));
        } catch (Throwable $exception) {
            $this->slips->delete($slipPath);

            throw $exception;
        }
    }
}
