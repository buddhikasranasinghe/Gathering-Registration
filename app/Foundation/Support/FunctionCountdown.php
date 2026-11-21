<?php

declare(strict_types=1);

namespace App\Foundation\Support;

use Carbon\CarbonImmutable;

final readonly class FunctionCountdown
{
    private CarbonImmutable $targetDate;

    public function __construct(CarbonImmutable $targetDate)
    {
        $this->targetDate = $targetDate->startOfDay();
    }

    public static function fromConfig(): self
    {
        return new self(
            CarbonImmutable::parse((string) config('gathering.function_date')),
        );
    }

    public function remainingDays(): int
    {
        return (int) CarbonImmutable::today()->diffInDays($this->targetDate, false);
    }

    public function isToday(): bool
    {
        return $this->remainingDays() === 0;
    }

    public function hasPassed(): bool
    {
        return $this->remainingDays() < 0;
    }
}
