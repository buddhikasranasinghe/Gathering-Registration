<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\UseCases\Requests\UpdateBatchRequest;

final class UpdateBatchUseCase
{
    public function handle(UpdateBatchRequest $request): void
    {
        $request->batch->update([
            'name' => $request->name,
            'excel_mapper_name' => $request->excelMapperName,
        ]);
    }
}
