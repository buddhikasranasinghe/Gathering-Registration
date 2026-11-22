<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Participation\Entities\Participant;
use App\Participation\Entities\ParticipantImportRun;
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
        $counts = ['added' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        $skippedRows = [];

        foreach ($rows as $index => $cells) {
            $rowNumber = $index + 2;
            $row = ParticipantImportRow::fromCells($cells, $rowNumber);

            if ($row === null) {
                $counts['skipped']++;

                if (count($skippedRows) < 50) {
                    $skippedRows[] = [
                        'row' => $rowNumber,
                        'reason' => 'The row has no name, NIC or phone number.',
                    ];
                }

                continue;
            }

            $counts[$this->upsert($row->attributes(), $importedAt)]++;
        }

        return [...$counts, 'skipped_rows' => $skippedRows];
    }

    /**
     * @param  array{
     *     name: string,
     *     call_name: ?string,
     *     batch: ?string,
     *     batch_number: ?int,
     *     phone: ?string,
     *     nic: ?string,
     *     spouse_attending: ?bool,
     *     spouse_name: ?string,
     *     import_key: string
     * }  $attributes
     * @return 'added'|'updated'|'unchanged'
     */
    private function upsert(array $attributes, \DateTimeInterface $importedAt): string
    {
        $participant = $this->findExisting($attributes);

        if ($participant === null) {
            Participant::query()->create([
                ...$attributes,
                'last_imported_at' => $importedAt,
            ]);

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

        return $changed ? 'updated' : 'unchanged';
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
}
