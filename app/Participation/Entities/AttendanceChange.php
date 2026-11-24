<?php

declare(strict_types=1);

namespace App\Participation\Entities;

use App\Identity\Entities\User;
use Database\Factories\AttendanceChangeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'participant_id',
    'participant_from',
    'participant_to',
    'relation_id',
    'relation_type',
    'relation_name_from',
    'relation_name_to',
    'relation_from',
    'relation_to',
    'changed_by',
])]
#[UseFactory(AttendanceChangeFactory::class)]
class AttendanceChange extends Model
{
    /** @use HasFactory<AttendanceChangeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'participant_from' => 'boolean',
            'participant_to' => 'boolean',
            'relation_type' => ParticipantRelation::class,
            'relation_from' => 'boolean',
            'relation_to' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * @return BelongsTo<Participant, $this>
     */
    public function relation(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'relation_id');
    }

    /**
     * @return array<int, string>
     */
    public function changes(): array
    {
        $changes = [];

        if ($this->participant_from !== $this->participant_to) {
            $changes[] = 'Participant: '.$this->attendanceLabel($this->participant_from).' → '.$this->attendanceLabel($this->participant_to);
        }

        $relationLabel = $this->relation_type?->label() ?? 'relation';

        if ($this->relation_from === null && $this->relation_to !== null) {
            $changes[] = 'Added '.$this->relation_name_to.' ('.$relationLabel.') as '.$this->attendanceLabel($this->relation_to);
        } elseif ($this->relation_to === null && $this->relation_from !== null) {
            $changes[] = 'Removed '.$this->relation_name_from.' ('.$relationLabel.')';
        } else {
            if ($this->relation_name_from !== $this->relation_name_to
                && ($this->relation_name_from !== null || $this->relation_name_to !== null)) {
                $changes[] = 'Relation name: '.($this->relation_name_from ?? 'none').' → '.($this->relation_name_to ?? 'none');
            }

            if ($this->relation_from !== $this->relation_to && $this->relation_from !== null) {
                $changes[] = ($this->relation_name_to ?? $relationLabel).': '.$this->attendanceLabel($this->relation_from).' → '.$this->attendanceLabel($this->relation_to);
            }
        }

        return $changes;
    }

    private function attendanceLabel(bool $attending): string
    {
        return $attending ? 'Attending' : 'Not attending';
    }
}
