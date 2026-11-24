<?php

declare(strict_types=1);

namespace App\Participation\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class TicketStorage
{
    public function disk(): string
    {
        return (string) config('tickets.storage_disk');
    }

    public function put(string $path, string $contents, ?string $disk = null): void
    {
        $stored = Storage::disk($disk ?? $this->disk())->put($path, $contents);

        if (! $stored) {
            throw new RuntimeException('The ticket PDF could not be stored.');
        }
    }

    public function exists(?string $path, ?string $disk = null): bool
    {
        return $path !== null && Storage::disk($disk ?? $this->disk())->exists($path);
    }

    public function get(string $path, ?string $disk = null): string
    {
        $contents = Storage::disk($disk ?? $this->disk())->get($path);

        if ($contents === null) {
            throw new RuntimeException('The ticket file is missing from storage.');
        }

        return $contents;
    }

    public function delete(?string $path, ?string $disk = null): void
    {
        if ($path !== null) {
            Storage::disk($disk ?? $this->disk())->delete($path);
        }
    }

    public function download(string $path, string $fileName, ?string $disk = null): StreamedResponse
    {
        return Storage::disk($disk ?? $this->disk())->download($path, $fileName, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
