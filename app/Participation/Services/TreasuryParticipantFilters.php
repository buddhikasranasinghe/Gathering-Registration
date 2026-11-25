<?php

declare(strict_types=1);

namespace App\Participation\Services;

use App\Participation\Entities\Participant;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class TreasuryParticipantFilters
{
    private const TABS = ['attention', 'settled'];

    private const PER_PAGE = 25;

    /**
     * @return array{activeTab: string, settledSearch: string, attentionSearch: string, attentionBatch: string}
     */
    public function fromRequest(Request $request): array
    {
        $activeTab = $request->string('tab')->toString();

        return [
            'activeTab' => in_array($activeTab, self::TABS, true) ? $activeTab : 'attention',
            'settledSearch' => trim($request->string('settled_search')->toString()),
            'attentionSearch' => trim($request->string('attention_search')->toString()),
            'attentionBatch' => trim($request->string('attention_batch')->toString()),
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    public function apply(Collection $rows, string $search, string $batchKey = ''): Collection
    {
        $filtered = $rows;
        $needle = mb_strtolower($search);

        if ($needle !== '') {
            $filtered = $filtered->filter(fn (array $row): bool => $this->matchesSearch($row['participant'], $needle));
        }

        if ($batchKey !== '') {
            $filtered = $filtered->filter(fn (array $row): bool => $this->matchesBatch($row['participant'], $batchKey));
        }

        return $filtered->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginate(Collection $rows, Request $request, string $pageName): LengthAwarePaginator
    {
        $page = max(1, $request->integer($pageName, 1));

        return new LengthAwarePaginator(
            $rows->forPage($page, self::PER_PAGE)->values()->all(),
            $rows->count(),
            self::PER_PAGE,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => $pageName,
            ],
        );
    }

    private function matchesSearch(Participant $participant, string $needle): bool
    {
        foreach ([$participant->name, $participant->call_name, $participant->phone, $participant->nic, $participant->email] as $value) {
            if ($value !== null && str_contains(mb_strtolower((string) $value), $needle)) {
                return true;
            }
        }

        return false;
    }

    private function matchesBatch(Participant $participant, string $batchKey): bool
    {
        if ($batchKey === 'unassigned') {
            return $participant->batch_id === null;
        }

        return (string) $participant->batch_id === $batchKey;
    }
}
