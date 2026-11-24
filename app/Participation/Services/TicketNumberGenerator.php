<?php

declare(strict_types=1);

namespace App\Participation\Services;

use Illuminate\Support\Facades\DB;

final class TicketNumberGenerator
{
    public function next(int $year): string
    {
        $value = DB::transaction(function () use ($year): int {
            $now = now();

            DB::table('ticket_number_sequences')->insertOrIgnore([
                'year' => $year,
                'next_value' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $sequence = DB::table('ticket_number_sequences')
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();

            $value = (int) $sequence->next_value;

            DB::table('ticket_number_sequences')
                ->where('year', $year)
                ->update([
                    'next_value' => $value + 1,
                    'updated_at' => $now,
                ]);

            return $value;
        });

        return sprintf(
            '%s-%d-%s',
            (string) config('tickets.number_prefix'),
            $year,
            str_pad((string) $value, (int) config('tickets.number_padding', 5), '0', STR_PAD_LEFT),
        );
    }
}
