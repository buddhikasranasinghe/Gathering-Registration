<?php

declare(strict_types=1);

namespace App\Participation\UseCases\Requests;

use App\Identity\Entities\User;
use App\Participation\Entities\Participant;
use Illuminate\Http\UploadedFile;

final readonly class RecordPaymentRequest
{
    public function __construct(
        public Participant $participant,
        public User $recordedBy,
        public float $amount,
        public string $method,
        public \DateTimeInterface $paidAt,
        public ?string $note,
        public ?UploadedFile $slip,
    ) {}
}
