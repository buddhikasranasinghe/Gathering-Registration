<?php

declare(strict_types=1);

namespace App\Participation\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Imagick;
use Throwable;

final class PaymentSlipStorage
{
    /**
     * @var array<int, string>
     */
    private const HEIC_EXTENSIONS = ['heic', 'heif'];

    /**
     * @var array<int, string>
     */
    private const HEIC_MIME_TYPES = ['image/heic', 'image/heif'];

    public function store(UploadedFile $slip): string
    {
        $startedAt = hrtime(true);
        $sizeBytes = $slip->getSize();
        $extension = strtolower($slip->getClientOriginalExtension());
        $mimeType = $slip->getMimeType();
        $isHeic = $this->isHeic($slip);
        $conversionMs = 0;

        if ($isHeic) {
            $conversionStartedAt = hrtime(true);
            $contents = $this->convertHeicToJpeg($slip->getRealPath());
            $conversionMs = $this->elapsedMilliseconds($conversionStartedAt);

            $path = 'payment-slips/'.Str::uuid().'.jpg';
            $storageStartedAt = hrtime(true);

            if (! Storage::disk()->put($path, $contents)) {
                throw ValidationException::withMessages([
                    'slip' => 'The payment slip could not be stored. Please try again.',
                ]);
            }
        } else {
            $storageStartedAt = hrtime(true);
            $path = $slip->store('payment-slips');
        }

        $storageMs = $this->elapsedMilliseconds($storageStartedAt);

        Log::info('Payment slip stored', [
            'disk' => config('filesystems.default'),
            'size_bytes' => $sizeBytes,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'heic' => $isHeic,
            'conversion_ms' => $conversionMs,
            'storage_ms' => $storageMs,
            'total_ms' => $this->elapsedMilliseconds($startedAt),
        ]);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null) {
            Storage::disk()->delete($path);
        }
    }

    private function isHeic(UploadedFile $slip): bool
    {
        return in_array(strtolower($slip->getClientOriginalExtension()), self::HEIC_EXTENSIONS, true)
            || in_array(strtolower($slip->getMimeType() ?? ''), self::HEIC_MIME_TYPES, true);
    }

    private function convertHeicToJpeg(string $source): string
    {
        $converted = $this->convertWithImagick($source);

        if ($converted !== null) {
            return $converted;
        }

        return $this->convertWithImageMagickCommand($source);
    }

    private function convertWithImagick(string $source): ?string
    {
        if (! extension_loaded('imagick')) {
            return null;
        }

        try {
            $image = new Imagick($source);
            $image->autoOrient();
            $image->setImageFormat('jpeg');
            $image->setImageCompressionQuality(90);
            $contents = $image->getImageBlob();
            $image->clear();

            return $contents;
        } catch (Throwable) {
            return null;
        }
    }

    private function convertWithImageMagickCommand(string $source): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'payment-slip-');

        if ($temporaryPath === false) {
            throw $this->conversionFailed();
        }

        $targetPath = $temporaryPath.'.jpg';

        try {
            foreach (['magick', 'convert'] as $binary) {
                $process = Process::run([
                    $binary,
                    $source,
                    '-auto-orient',
                    '-quality',
                    '90',
                    $targetPath,
                ]);

                if ($process->failed() || ! is_file($targetPath)) {
                    @unlink($targetPath);

                    continue;
                }

                $contents = file_get_contents($targetPath);

                if ($contents !== false) {
                    return $contents;
                }

                @unlink($targetPath);
            }

            throw $this->conversionFailed();
        } finally {
            @unlink($temporaryPath);
            @unlink($targetPath);
        }
    }

    private function elapsedMilliseconds(int $startedAt): int
    {
        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }

    private function conversionFailed(): ValidationException
    {
        return ValidationException::withMessages([
            'slip' => 'The HEIC payment slip could not be converted. Please upload a JPEG, PNG, or PDF instead.',
        ]);
    }
}
