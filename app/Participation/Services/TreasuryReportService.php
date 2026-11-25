<?php

declare(strict_types=1);

namespace App\Participation\Services;

use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantProgressStatus;
use Illuminate\Support\Collection;

final class TreasuryReportService
{
    public function __construct(private TicketPriceCalculator $prices) {}

    /**
     * @return array{
     *     totals: array{participants: int, expected: float, collected: float, remaining: float, refundable: float, settled: int, needsAttention: int},
     *     batches: Collection<int, array{batchId: ?int, name: string, participants: int, expected: float, collected: float, remaining: float, refundable: float, settled: int, needsAttention: int}>,
     *     settled: Collection<int, array<string, mixed>>,
     *     attention: Collection<int, array<string, mixed>>
     * }
     */
    public function overview(): array
    {
        return $this->build(null);
    }

    /**
     * @return array{
     *     batch: Batch,
     *     totals: array{participants: int, expected: float, collected: float, remaining: float, refundable: float, settled: int, needsAttention: int},
     *     rows: Collection<int, array<string, mixed>>,
     *     settled: Collection<int, array<string, mixed>>,
     *     attention: Collection<int, array<string, mixed>>
     * }
     */
    public function forBatch(Batch $batch): array
    {
        return $this->build($batch);
    }

    /**
     * @return array<string, mixed>
     */
    private function build(?Batch $batch): array
    {
        $batches = Batch::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id', 'name']);

        $participants = Participant::query()
            ->when($batch !== null, fn ($query) => $query->where('batch_id', $batch->getKey()))
            ->with([
                'batch:id,name',
                'relatedParticipants' => fn ($query) => $query
                    ->select('id', 'primary_participant_id', 'name', 'relation', 'attending', 'archived_at')
                    ->orderBy('name')
                    ->orderBy('id'),
                'relatedParticipants.payments' => fn ($query) => $query->select('id', 'participant_id', 'amount'),
                'payments' => fn ($query) => $query->select('id', 'participant_id', 'amount'),
                'issuedTickets' => fn ($query) => $query
                    ->select('id', 'participant_id', 'status', 'ticket_price', 'issued_at')
                    ->orderByDesc('issued_at')
                    ->orderByDesc('id'),
            ])
            ->whereNull('archived_at')
            ->whereNull('primary_participant_id')
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $rows = $participants->map(fn (Participant $participant): array => $this->figuresFor($participant))->values();

        $settled = $rows
            ->filter(fn (array $row): bool => in_array($row['status'], [
                ParticipantProgressStatus::PaymentComplete,
                ParticipantProgressStatus::TicketIssued,
            ], true))
            ->values();

        $attention = $rows
            ->filter(fn (array $row): bool => $this->needsAttention($row))
            ->values();

        $totals = [
            'participants' => $rows->count(),
            'expected' => round((float) $rows->sum('expected'), 2),
            'collected' => round((float) $rows->sum('collected'), 2),
            'remaining' => round((float) $rows->sum('remaining'), 2),
            'refundable' => round((float) $rows->sum('refundable'), 2),
            'settled' => $settled->count(),
            'needsAttention' => $attention->count(),
        ];

        $report = [
            'totals' => $totals,
            'settled' => $settled,
            'attention' => $attention,
        ];

        if ($batch !== null) {
            return [...$report, 'batch' => $batch, 'rows' => $rows];
        }

        return [...$report, 'batches' => $this->batchBreakdown($batches, $rows)];
    }

    /**
     * @param  Collection<int, Batch>  $batches
     * @param  Collection<int, array<string, mixed>>  $rows
     * @return Collection<int, array<string, mixed>>
     */
    private function batchBreakdown(Collection $batches, Collection $rows): Collection
    {
        $keys = $batches->map(fn (Batch $batch): string => (string) $batch->getKey())->all();

        if ($rows->contains(fn (array $row): bool => $row['participant']->batch_id === null)) {
            $keys[] = 'unassigned';
        }

        return collect($keys)
            ->map(function (string $key) use ($batches, $rows): array {
                $isUnassigned = $key === 'unassigned';
                $batch = $isUnassigned
                    ? null
                    : $batches->first(fn (Batch $candidate): bool => (string) $candidate->getKey() === $key);

                $groupRows = $rows->filter(function (array $row) use ($key): bool {
                    $batchId = $row['participant']->batch_id;

                    return $this->groupKey($batchId === null ? null : (int) $batchId) === $key;
                })->values();

                $settled = $groupRows->filter(fn (array $row): bool => in_array($row['status'], [
                    ParticipantProgressStatus::PaymentComplete,
                    ParticipantProgressStatus::TicketIssued,
                ], true))->count();

                return [
                    'batch' => $batch,
                    'batchId' => $isUnassigned ? null : $batch?->getKey(),
                    'name' => $isUnassigned ? 'Unassigned' : ($batch?->name ?? 'Unknown'),
                    'participants' => $groupRows->count(),
                    'expected' => round((float) $groupRows->sum('expected'), 2),
                    'collected' => round((float) $groupRows->sum('collected'), 2),
                    'remaining' => round((float) $groupRows->sum('remaining'), 2),
                    'refundable' => round((float) $groupRows->sum('refundable'), 2),
                    'settled' => $settled,
                    'needsAttention' => $groupRows->filter(fn (array $row): bool => $this->needsAttention($row))->count(),
                ];
            })
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function figuresFor(Participant $participant): array
    {
        $activeRelations = $participant->relatedParticipants
            ->whereNull('archived_at')
            ->values();
        $participant->setRelation('relatedParticipants', $activeRelations);

        $activeTicket = $participant->issuedTickets
            ->first(fn ($ticket): bool => $ticket->isActive());

        $ticketPrice = $activeTicket?->ticket_price;
        $expected = $ticketPrice !== null
            ? round((float) $ticketPrice, 2)
            : round($this->prices->calculate($participant)['total'], 2);

        $collected = (float) $participant->payments->sum(fn ($payment): float => (float) $payment->amount);

        foreach ($activeRelations as $relation) {
            $collected += (float) $relation->payments->sum(fn ($payment): float => (float) $payment->amount);
        }

        $collected = round($collected, 2);
        $remaining = round(max(0.0, $expected - $collected), 2);
        $refundable = round(max(0.0, $collected - $expected), 2);

        return [
            'participant' => $participant,
            'name' => $participant->name,
            'batchName' => $participant->batch?->name ?? 'Unassigned',
            'expected' => $expected,
            'collected' => $collected,
            'remaining' => $remaining,
            'refundable' => $refundable,
            'status' => $this->status($activeTicket !== null, $expected, $collected, $remaining),
            'priceSource' => $ticketPrice !== null ? 'issued_ticket' : 'recalculated',
        ];
    }

    private function status(bool $hasActiveTicket, float $expected, float $collected, float $remaining): ParticipantProgressStatus
    {
        return match (true) {
            $hasActiveTicket => ParticipantProgressStatus::TicketIssued,
            $remaining <= 0.0 && ($expected > 0.0 || $collected > 0.0) => ParticipantProgressStatus::PaymentComplete,
            $collected > 0.0 => ParticipantProgressStatus::PaymentIncomplete,
            default => ParticipantProgressStatus::NotPaid,
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function needsAttention(array $row): bool
    {
        return $row['remaining'] > 0.0;
    }

    private function groupKey(?int $batchId): string
    {
        return $batchId === null ? 'unassigned' : (string) $batchId;
    }
}
