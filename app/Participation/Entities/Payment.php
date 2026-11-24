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

#[Fillable([
    'participant_id',
    'amount',
    'method',
    'paid_at',
    'slip_path',
    'recorded_by',
    'recorded_by_name',
    'updated_by',
    'updated_by_name',
])]
#[UseFactory(PaymentFactory::class)]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    public const SLIP_ACCEPT = '.pdf,.jpg,.jpeg,.png,.heic,.heif';

    public const SLIP_MIMES = 'pdf,jpg,jpeg,png,heic,heif';

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'date',
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

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function addedBy(): string
    {
        return $this->recorded_by_name ?? $this->recordedBy?->name ?? '—';
    }

    public function lastUpdatedBy(): ?string
    {
        return $this->updated_by_name ?? $this->updatedBy?->name;
    }

    public function slipPreviewKind(): ?string
    {
        if ($this->slip_path === null) {
            return null;
        }

        $extension = strtolower(pathinfo($this->slip_path, PATHINFO_EXTENSION));

        return in_array($extension, ['jpg', 'jpeg', 'png', 'heic', 'heif'], true) ? 'image' : 'pdf';
    }

    public function slipDisplayName(): ?string
    {
        if ($this->slip_path === null) {
            return null;
        }

        return strtoupper(pathinfo($this->slip_path, PATHINFO_EXTENSION)).' slip';
    }
}
