<?php

declare(strict_types=1);

namespace App\Participation\Entities;

use App\Identity\Entities\User;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['participant_id', 'amount', 'method', 'paid_at', 'slip_path', 'note', 'recorded_by'])]
#[UseFactory(PaymentFactory::class)]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'method' => PaymentMethod::class,
    ];

    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
