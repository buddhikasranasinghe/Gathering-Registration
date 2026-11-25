<?php

declare(strict_types=1);

namespace App\Participation\IO\Http\Controllers;

use App\Foundation\IO\Http\Controllers\Controller;
use App\Identity\Entities\User;
use App\Participation\Services\TreasuryParticipantFilters;
use App\Participation\Services\TreasuryReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreasuryOverviewController extends Controller
{
    public function __invoke(Request $request, TreasuryReportService $reports, TreasuryParticipantFilters $filters): View|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isBatchScoped()) {
            if ($user->batch_id === null) {
                return redirect()->route('participants.index');
            }

            return redirect()->route('treasury.batches.show', $user->batch_id);
        }

        $report = $reports->overview();
        $viewFilters = $filters->fromRequest($request);

        return view('treasury.index', [
            ...$report,
            'settled' => $filters->paginate(
                $filters->apply($report['settled'], $viewFilters['settledSearch']),
                $request,
                'settled_page',
            ),
            'attention' => $filters->paginate(
                $filters->apply($report['attention'], $viewFilters['attentionSearch'], $viewFilters['attentionBatch']),
                $request,
                'attention_page',
            ),
            'filters' => $viewFilters,
        ]);
    }
}
