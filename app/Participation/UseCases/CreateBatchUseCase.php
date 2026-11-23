<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\Batch;
use App\Participation\UseCases\Requests\CreateBatchRequest;

final class CreateBatchUseCase
{
    public function handle(CreateBatchRequest $request): Batch
    {
        return Batch::query()->create([
            'name' => $request->name,
            'excel_mapper_name' => $request->excelMapperName,
        ]);
    }
}
