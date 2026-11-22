<?php

declare(strict_types=1);

namespace App\Participation\Entities;

use App\Identity\Entities\User;
use Database\Factories\ParticipantImportRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable([
    'requested_by',
    'source_name',
    'status',
    'started_at',
    'finished_at',
    'added_count',
    'updated_count',
    'unchanged_count',
    'skipped_count',
    'failure_reason',
    'skipped_rows',
])]
#[UseFactory(ParticipantImportRunFactory::class)]
class ParticipantImportRun extends Model
{
    /** @use HasFactory<ParticipantImportRunFactory> */
    use HasFactory;

    protected $table = 'participant_import_runs';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'added_count' => 'integer',
            'updated_count' => 'integer',
            'unchanged_count' => 'integer',
            'skipped_count' => 'integer',
            'skipped_rows' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function isActive(): bool
    {
        return $this->status === ImportStatus::Running;
    }

    public function isCompleted(): bool
    {
        return $this->status === ImportStatus::Completed;
    }

    public function hasFailed(): bool
    {
        return $this->status === ImportStatus::Failed;
    }

    public function markCompleted(array $result): void
    {
        $this->update([
            'status' => ImportStatus::Completed,
            'finished_at' => now(),
            'added_count' => $result['added'],
            'updated_count' => $result['updated'],
            'unchanged_count' => $result['unchanged'],
            'skipped_count' => $result['skipped'],
            'skipped_rows' => $result['skipped_rows'],
        ]);
    }

    public function markFailed(string $reason): void
    {
        $this->update([
            'status' => ImportStatus::Failed,
            'finished_at' => now(),
            'failure_reason' => Str::limit($reason, 1000),
        ]);
    }

    public function summary(): string
    {
        return sprintf(
            '%d added, %d updated, %d unchanged, %d skipped',
            $this->added_count,
            $this->updated_count,
            $this->unchanged_count,
            $this->skipped_count,
        );
    }
}
