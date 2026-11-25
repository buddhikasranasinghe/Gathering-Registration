<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Participation\Entities\Batch;
use App\Participation\Services\TreasuryParticipantFilters;
use App\Participation\Services\TreasuryReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreasuryBatchController extends Controller
{
    public function __invoke(Request $request, Batch $batch, TreasuryReportService $reports, TreasuryParticipantFilters $filters): View
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isBatchScoped() && $user->batch_id !== $batch->getKey()) {
            abort(404);
        }

        $report = $reports->forBatch($batch);
        $viewFilters = $filters->fromRequest($request);

        return view('treasury.batches.show', [
            ...$report,
            'settled' => $filters->paginate(
                $filters->apply($report['settled'], $viewFilters['settledSearch']),
                $request,
                'settled_page',
            ),
            'attention' => $filters->paginate(
                $filters->apply($report['attention'], $viewFilters['attentionSearch']),
                $request,
                'attention_page',
            ),
            'filters' => $viewFilters,
        ]);
    }
}
