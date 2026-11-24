<?php

declare(strict_types=1);

namespace App\Participation\UseCases\Requests;

use App\Identity\Entities\User;
use App\Participation\Entities\Payment;
use Illuminate\Http\UploadedFile;

final readonly class UpdatePaymentRequest
{
    public function __construct(
        public Payment $payment,
        public User $updatedBy,
        public float $amount,
        public string $method,
        public \DateTimeInterface $paidAt,
        public ?UploadedFile $slip,
        public bool $removeSlip,
    ) {}
}
