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

#[Fillable(['participant_id', 'ticket_number', 'issued_at', 'status', 'file_path', 'issued_by'])]
#[UseFactory(IssuedTicketFactory::class)]
class IssuedTicket extends Model
{
    /** @use HasFactory<IssuedTicketFactory> */
    use HasFactory;

    protected $casts = [
        'issued_at' => 'datetime',
        'status' => TicketStatus::class,
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
