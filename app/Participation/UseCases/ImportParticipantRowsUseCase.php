<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Identity\Entities\User;
use App\Participation\Entities\Batch;
use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantImportRun;
use App\Participation\Entities\ParticipantRelation;
use App\Participation\Services\ParticipantImportRow;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ImportParticipantRowsUseCase
{
    /**
     * @param  array<int, array<int, mixed>>  $rows
     *
     * @throws Throwable
     */
    public function handle(ParticipantImportRun $run, array $rows): void
    {
        try {
            $result = DB::transaction(fn (): array => $this->importRows($rows, $run));
        } catch (Throwable $exception) {
            $run->markFailed($exception->getMessage());

            throw $exception;
        }

        $run->markCompleted($result);
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{added: int, updated: int, unchanged: int, skipped: int, skipped_rows: array<int, array{row: int, reason: string}>}
     */
    private function importRows(array $rows, ParticipantImportRun $run): array
    {
        $importedAt = $run->started_at ?? now();
        $parsedRows = $this->parsedRows($rows);
        $batchIds = $this->batchIds($parsedRows);
        $counts = ['added' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        $skippedRows = [];

        foreach ($parsedRows as $index => $row) {
            if ($row === null) {
                $counts['skipped']++;

                if (count($skippedRows) < 50) {
                    $skippedRows[] = [
                        'row' => $index + 2,
                        'reason' => 'The row has no name, NIC or phone number.',
                    ];
                }

                continue;
            }

            $attributes = $row->attributes();
            $batchMapperName = $attributes['batch_mapper_name'];
            unset($attributes['batch_mapper_name']);

            if ($batchMapperName !== null && ! array_key_exists($batchMapperName, $batchIds)) {
                $counts['skipped']++;

                if (count($skippedRows) < 50) {
                    $skippedRows[] = [
                        'row' => $row->rowNumber(),
                        'reason' => 'Unknown batch mapping: '.$batchMapperName.'.',
                    ];
                }

                continue;
            }

            $counts[$this->upsert([
                ...$attributes,
                'batch_id' => $batchMapperName === null ? null : $batchIds[$batchMapperName],
            ], $importedAt)]++;
        }

        return [...$counts, 'skipped_rows' => $skippedRows];
    }

    /**
     * @param  array{
     *     name: string,
     *     call_name: ?string,
     *     phone: ?string,
     *     nic: ?string,
     *     spouse_attending: bool,
     *     spouse_name: ?string,
     *     import_key: string,
     *     batch_id: ?int
     * }  $attributes
     * @return 'added'|'updated'|'unchanged'
     */
    private function upsert(array $attributes, \DateTimeInterface $importedAt): string
    {
        $spouseName = $attributes['spouse_name'];
        $spouseAttending = $attributes['spouse_attending'];
        unset($attributes['spouse_name'], $attributes['spouse_attending']);

        $participant = $this->findExisting($attributes);

        if ($participant === null) {
            $participant = Participant::query()->create([
                ...$attributes,
                'last_imported_at' => $importedAt,
            ]);

            $this->syncUserBatch($participant);
            $this->syncSpouseRelation($participant, $spouseName, $spouseAttending, $importedAt);

            return 'added';
        }

        if ($participant->isArchived()) {
            $participant->forceFill(['last_imported_at' => $importedAt])->save();

            return 'unchanged';
        }

        $participant->fill($attributes);
        $changed = $participant->isDirty();
        $participant->last_imported_at = $importedAt;
        $participant->save();
        $this->syncUserBatch($participant);
        $this->syncSpouseRelation($participant, $spouseName, $spouseAttending, $importedAt);

        return $changed ? 'updated' : 'unchanged';
    }

    private function syncSpouseRelation(
        Participant $participant,
        ?string $spouseName,
        bool $spouseAttending,
        \DateTimeInterface $importedAt,
    ): void {
        if ($spouseName === null) {
            return;
        }

        $relation = $participant->relatedParticipants()
            ->where('relation', ParticipantRelation::Spouse->value)
            ->where('name', $spouseName)
            ->first()
            ?? $participant->relatedParticipants()
                ->where('relation', ParticipantRelation::Spouse->value)
                ->where('attendance_manually_set', false)
                ->orderBy('id')
                ->first();

        if ($relation === null) {
            $participant->relatedParticipants()->create([
                'name' => $spouseName,
                'batch_id' => $participant->batch_id,
                'attending' => $spouseAttending,
                'attendance_manually_set' => false,
                'relation' => ParticipantRelation::Spouse,
                'last_imported_at' => $importedAt,
            ]);

            return;
        }

        if ($relation->attendance_manually_set) {
            return;
        }

        $relation->fill([
            'name' => $spouseName,
            'batch_id' => $participant->batch_id,
            'attending' => $spouseAttending,
            'last_imported_at' => $importedAt,
        ])->save();
    }

    private function syncUserBatch(Participant $participant): void
    {
        if ($participant->email === null) {
            return;
        }

        User::query()
            ->where('email', $participant->email)
            ->update(['batch_id' => $participant->batch_id]);
    }

    /**
     * @param  array{phone: ?string, nic: ?string}  $attributes
     */
    private function findExisting(array $attributes): ?Participant
    {
        if ($attributes['nic'] !== null) {
            $byNic = Participant::query()
                ->whereNotNull('import_key')
                ->where('nic', $attributes['nic'])
                ->orderBy('id')
                ->first();

            if ($byNic !== null) {
                return $byNic;
            }
        }

        if ($attributes['phone'] === null) {
            return null;
        }

        $byPhone = Participant::query()
            ->whereNotNull('import_key')
            ->where('phone', $attributes['phone'])
            ->orderBy('id')
            ->first();

        if ($byPhone === null || ($attributes['nic'] !== null && $byPhone->nic !== null && $byPhone->nic !== $attributes['nic'])) {
            return null;
        }

        return $byPhone;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     * @return array<int, ?ParticipantImportRow>
     */
    private function parsedRows(array $rows): array
    {
        return array_map(
            fn (array $cells, int $index): ?ParticipantImportRow => ParticipantImportRow::fromCells($cells, $index + 2),
            $rows,
            array_keys($rows),
        );
    }

    /**
     * @param  array<int, ?ParticipantImportRow>  $rows
     * @return array<string, int>
     */
    private function batchIds(array $rows): array
    {
        $mapperNames = collect($rows)
            ->filter()
            ->map(fn (ParticipantImportRow $row): ?string => $row->attributes()['batch_mapper_name'])
            ->filter()
            ->unique()
            ->values();

        if ($mapperNames->isEmpty()) {
            return [];
        }

        return Batch::query()
            ->whereIn('excel_mapper_name', $mapperNames)
            ->pluck('id', 'excel_mapper_name')
            ->map(fn (int $id): int => $id)
            ->all();
    }
}
