<?php

declare(strict_types=1);

namespace App\Participation\Entities;

use App\Identity\Entities\User;
use Database\Factories\IssuedTicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'participant_id',
    'ticket_number',
    'issued_at',
    'status',
    'file_path',
    'file_disk',
    'ticket_price',
    'attendees',
    'issued_by',
    'issued_by_name',
    'deactivated_at',
    'deactivated_by',
    'deactivated_by_name',
    'emailed_at',
    'emailed_to',
    'email_send_count',
])]
#[UseFactory(IssuedTicketFactory::class)]
class IssuedTicket extends Model
{
    /** @use HasFactory<IssuedTicketFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'status' => TicketStatus::class,
            'attendees' => 'array',
            'ticket_price' => 'decimal:2',
            'deactivated_at' => 'datetime',
            'emailed_at' => 'datetime',
        ];
    }

    /**
     * The participant this ticket was issued for.
     *
     * @return BelongsTo<Participant, $this>
     */
    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    /**
     * The administrator who issued the ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * The administrator who deactivated the ticket.
     *
     * @return BelongsTo<User, $this>
     */
    public function deactivatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deactivated_by');
    }

    public function getRouteKeyName(): string
    {
        return 'ticket_number';
    }

    public function isActive(): bool
    {
        return $this->status === TicketStatus::Active;
    }
}
