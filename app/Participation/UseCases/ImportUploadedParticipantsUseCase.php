<?php

declare(strict_types=1);

namespace App\Participation\UseCases;

use App\Identity\Entities\User;
use App\Participation\Entities\ImportStatus;
use App\Participation\Entities\ParticipantImportRun;
use App\Participation\Exceptions\ParticipantImportAlreadyRunningException;
use App\Participation\Services\UploadedParticipantFileReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

final class ImportUploadedParticipantsUseCase
{
    public function __construct(
        private readonly UploadedParticipantFileReader $reader,
        private readonly ImportParticipantRowsUseCase $importRows,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(User $requestedBy, UploadedFile $file): ParticipantImportRun
    {
        $run = $this->createRun($requestedBy, Str::limit($file->getClientOriginalName(), 255, ''));

        try {
            $this->importRows->handle($run, $this->reader->rows($file));
        } catch (Throwable $exception) {
            if (! $run->hasFailed()) {
                $run->markFailed($exception->getMessage());
            }

            throw $exception;
        }

        return $run->refresh();
    }

    /**
     * @throws ParticipantImportAlreadyRunningException
     */
    private function createRun(User $requestedBy, string $sourceName): ParticipantImportRun
    {
        return DB::transaction(function () use ($requestedBy, $sourceName): ParticipantImportRun {
            $active = ParticipantImportRun::query()
                ->where('status', ImportStatus::Running)
                ->lockForUpdate()
                ->exists();

            if ($active) {
                throw new ParticipantImportAlreadyRunningException;
            }

            return ParticipantImportRun::query()->create([
                'requested_by' => $requestedBy->getKey(),
                'source_name' => $sourceName,
                'status' => ImportStatus::Running,
                'started_at' => now(),
            ]);
        });
    }
}
