<?php

declare(strict_types=1);

namespace App\Participation\UseCases\Requests;

final readonly class CreateBatchRequest
{
    public function __construct(
        public string $name,
        public string $excelMapperName,
    ) {}
}
