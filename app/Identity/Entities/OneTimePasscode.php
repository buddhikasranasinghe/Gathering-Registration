<?php

declare(strict_types=1);

namespace App\Identity\Entities;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['email', 'code_hash', 'pending_name', 'attempts', 'expires_at', 'consumed_at', 'ip_address'])]
class OneTimePasscode extends Model
{
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public static function latestFor(string $email): ?self
    {
        return static::query()
            ->where('email', $email)
            ->latest('id')
            ->first();
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function hasExhaustedAttempts(int $maxAttempts): bool
    {
        return $this->attempts >= $maxAttempts;
    }

    public function consume(): void
    {
        $this->update(['consumed_at' => now()]);
    }

    public function recordFailedAttempt(): void
    {
        $this->attempts++;
        $this->save();
    }

    public function secondsUntilResend(int $cooldownSeconds): int
    {
        $availableAt = $this->created_at->copy()->addSeconds($cooldownSeconds);

        return max(0, $availableAt->getTimestamp() - now()->getTimestamp());
    }
}
