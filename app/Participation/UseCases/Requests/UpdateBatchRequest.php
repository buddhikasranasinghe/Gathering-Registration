<?php

declare(strict_types=1);

namespace App\Participation\UseCases\Requests;

use App\Participation\Entities\Batch;

final readonly class UpdateBatchRequest
{
    public function __construct(
        public Batch $batch,
        public string $name,
        public string $excelMapperName,
    ) {}
}
