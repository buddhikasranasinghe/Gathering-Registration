<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Services\PaymentSlipStorage;
use App\Participation\UseCases\Requests\DeletePaymentRequest;
use Illuminate\Support\Facades\DB;

final class DeletePaymentUseCase
{
    public function __construct(private PaymentSlipStorage $slips) {}

    public function handle(DeletePaymentRequest $request): void
    {
        $payment = $request->payment;
        $slipPath = $payment->slip_path;

        DB::transaction(function () use ($payment): void {
            $payment->delete();
        });

        $this->slips->delete($slipPath);
    }
}
