<?php

declare(strict_types=1);

namespace App\Participation\Services;

use Illuminate\Http\UploadedFile;
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
        if ($this->isHeic($slip)) {
            return $this->storeHeicAsJpeg($slip);
        }

        return $slip->store('payment-slips');
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

    private function storeHeicAsJpeg(UploadedFile $slip): string
    {
        $contents = $this->convertHeicToJpeg($slip->getRealPath());
        $path = 'payment-slips/'.Str::uuid().'.jpg';

        if (! Storage::disk()->put($path, $contents)) {
            throw ValidationException::withMessages([
                'slip' => 'The payment slip could not be stored. Please try again.',
            ]);
        }

        return $path;
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

    private function conversionFailed(): ValidationException
    {
        return ValidationException::withMessages([
            'slip' => 'The HEIC payment slip could not be converted. Please upload a JPEG, PNG, or PDF instead.',
        ]);
    }
}
