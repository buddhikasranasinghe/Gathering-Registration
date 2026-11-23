<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Participation\Entities\Batch;
use App\Participation\IO\Http\Requests\StoreBatchRequest;
use App\Participation\IO\Http\Requests\UpdateBatchRequest;
use App\Participation\UseCases\CreateBatchUseCase;
use App\Participation\UseCases\Requests\CreateBatchRequest as CreateBatch;
use App\Participation\UseCases\Requests\UpdateBatchRequest as UpdateBatch;
use App\Participation\UseCases\UpdateBatchUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BatchController extends Controller
{
    public function index(): View
    {
        return view('batches.index', [
            'batches' => Batch::query()
                ->withCount('participants')
                ->orderBy('name')
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('batches.create');
    }

    public function store(
        StoreBatchRequest $request,
        CreateBatchUseCase $createBatch,
    ): RedirectResponse {
        $batch = $createBatch->handle(new CreateBatch(
            name: $request->string('name')->toString(),
            excelMapperName: $request->string('excel_mapper_name')->toString(),
        ));

        return redirect()
            ->route('batches.index')
            ->with('status', $batch->name.' was created.');
    }

    public function edit(Batch $batch): View
    {
        return view('batches.edit', ['batch' => $batch]);
    }

    public function update(
        UpdateBatchRequest $request,
        Batch $batch,
        UpdateBatchUseCase $updateBatch,
    ): RedirectResponse {
        $updateBatch->handle(new UpdateBatch(
            batch: $batch,
            name: $request->string('name')->toString(),
            excelMapperName: $request->string('excel_mapper_name')->toString(),
        ));

        return redirect()
            ->route('batches.index')
            ->with('status', $batch->name.' was updated.');
    }
}
