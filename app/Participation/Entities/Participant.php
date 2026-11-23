<?php

declare(strict_types=1);

namespace App\Participation\Entities;

use App\Identity\Entities\User;
use Database\Factories\ParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'email',
    'call_name',
    'batch_id',
    'phone',
    'nic',
    'spouse_attending',
    'spouse_name',
    'import_key',
    'last_imported_at',
    'archived_at',
    'archive_note',
    'archived_by',
])]
#[UseFactory(ParticipantFactory::class)]
class Participant extends Model
{
    /** @use HasFactory<ParticipantFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'spouse_attending' => 'boolean',
            'last_imported_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * The administrator who archived the participant.
     *
     * @return BelongsTo<User, $this>
     */
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'archived_by');
    }

    /**
     * The participant's batch.
     *
     * @return BelongsTo<Batch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    /**
     * Payments recorded for this participant.
     *
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Tickets issued to this participant.
     *
     * @return HasMany<IssuedTicket, $this>
     */
    public function issuedTickets(): HasMany
    {
        return $this->hasMany(IssuedTicket::class);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }
}
