<?php

declare(strict_types=1);

namespace App\Participation\Services;

use Illuminate\Support\Str;

final class ParticipantImportRow
{
    /**
     * @param  array{
     *     name: string,
     *     call_name: ?string,
     *     batch: ?string,
     *     batch_number: ?int,
     *     phone: ?string,
     *     nic: ?string,
     *     spouse_attending: ?bool,
     *     spouse_name: ?string,
     *     import_key: string
     * }  $attributes
     */
    private function __construct(
        private readonly int $rowNumber,
        private readonly array $attributes,
    ) {}

    /**
     * @param  array<int, mixed>  $cells
     */
    public static function fromCells(array $cells, int $rowNumber): ?self
    {
        $cells = array_map(
            fn (mixed $cell): string => is_scalar($cell) ? trim((string) $cell) : '',
            array_pad($cells, 12, ''),
        );

        $name = Str::squish($cells[1]);
        $phone = self::normalizePhone($cells[4]);
        $nic = self::normalizeNic($cells[9]);

        if ($name === '' || ($phone === null && $nic === null)) {
            return null;
        }

        return new self($rowNumber, [
            'name' => $name,
            'call_name' => self::text($cells[2]),
            'batch' => self::text($cells[3]),
            'batch_number' => self::batchNumber($cells[3]),
            'phone' => $phone,
            'nic' => $nic,
            'spouse_attending' => self::yesNo($cells[5]),
            'spouse_name' => self::text($cells[10]),
            'import_key' => $nic !== null ? 'nic:'.$nic : 'phone:'.$phone,
        ]);
    }

    public function rowNumber(): int
    {
        return $this->rowNumber;
    }

    /**
     * @return array{
     *     name: string,
     *     call_name: ?string,
     *     batch: ?string,
     *     batch_number: ?int,
     *     phone: ?string,
     *     nic: ?string,
     *     spouse_attending: ?bool,
     *     spouse_name: ?string,
     *     import_key: string
     * }
     */
    public function attributes(): array
    {
        return $this->attributes;
    }

    private static function text(string $value): ?string
    {
        $value = Str::squish($value);

        return $value === '' ? null : $value;
    }

    private static function batchNumber(string $value): ?int
    {
        if (preg_match('/^\s*(\d{1,2})\b/', $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[1];
    }

    private static function yesNo(string $value): ?bool
    {
        return match (Str::lower(Str::squish($value))) {
            'yes' => true,
            'no' => false,
            default => null,
        };
    }

    private static function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        if (str_starts_with($digits, '94') && strlen($digits) === 11) {
            $digits = '0'.substr($digits, 2);
        }

        if (strlen($digits) === 9) {
            $digits = '0'.$digits;
        }

        return preg_match('/^0\d{9}$/', $digits) === 1 ? $digits : null;
    }

    private static function normalizeNic(string $value): ?string
    {
        $nic = Str::upper(preg_replace('/[^A-Za-z0-9]/', '', $value) ?? '');

        if (preg_match('/^\d{9}[VX]$|^\d{12}$/', $nic) === 1) {
            return $nic;
        }

        return null;
    }
}
