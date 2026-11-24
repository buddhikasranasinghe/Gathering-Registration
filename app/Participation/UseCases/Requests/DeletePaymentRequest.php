<?php

declare(strict_types=1);

namespace App\Participation\UseCases\Requests;

use App\Participation\Entities\Payment;

final readonly class DeletePaymentRequest
{
    public function __construct(public Payment $payment) {}
}
