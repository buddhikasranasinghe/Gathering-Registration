<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\Payment;
use App\Participation\Services\PaymentSlipStorage;
use App\Participation\UseCases\Requests\UpdatePaymentRequest;
use Illuminate\Support\Facades\DB;
use Throwable;

final class UpdatePaymentUseCase
{
    public function __construct(private PaymentSlipStorage $slips) {}

    public function handle(UpdatePaymentRequest $request): Payment
    {
        $payment = $request->payment;
        $previousSlipPath = $payment->slip_path;
        $newSlipPath = $request->slip !== null ? $this->slips->store($request->slip) : null;
        $removingSlip = $request->removeSlip && $newSlipPath === null;
        $slipPath = $newSlipPath ?? ($removingSlip ? null : $payment->slip_path);

        try {
            DB::transaction(function () use ($payment, $request, $slipPath): void {
                $payment->update([
                    'amount' => $request->amount,
                    'method' => $request->method,
                    'paid_at' => $request->paidAt,
                    'slip_path' => $slipPath,
                    'updated_by' => $request->updatedBy->getKey(),
                    'updated_by_name' => $request->updatedBy->name,
                ]);
            });
        } catch (Throwable $exception) {
            if ($newSlipPath !== null) {
                $this->slips->delete($newSlipPath);
            }

            throw $exception;
        }

        if ($previousSlipPath !== null && ($newSlipPath !== null || $removingSlip)) {
            $this->slips->delete($previousSlipPath);
        }

        return $payment->refresh();
    }
}
