<?php

declare(strict_types=1);

namespace App\Participation\Services;

use App\Participation\Exceptions\ParticipantImportException;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use Throwable;

final class UploadedParticipantFileReader
{
    private const MAX_ROWS = 5001;

    /**
     * @return array<int, array<int, mixed>>
     *
     * @throws ParticipantImportException
     */
    public function rows(UploadedFile $file): array
    {
        try {
            $spreadsheet = $this->reader($file)->load($file->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();

            if ($worksheet->getHighestRow() > self::MAX_ROWS) {
                throw new ParticipantImportException('The spreadsheet cannot contain more than 5,000 data rows.');
            }

            $rows = $worksheet->toArray(null, true, true, false);
            $spreadsheet->disconnectWorksheets();
        } catch (ParticipantImportException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new ParticipantImportException('The uploaded spreadsheet could not be read.', previous: $exception);
        }

        if (count($rows) < 2) {
            throw new ParticipantImportException('The uploaded spreadsheet has no data rows.');
        }

        return array_values(array_slice($rows, 1));
    }

    private function reader(UploadedFile $file): IReader
    {
        if ($file->extension() === 'csv') {
            $reader = new Csv;
            $reader->setDelimiter($this->csvDelimiter($file));

            return $reader;
        }

        return IOFactory::createReader('Xlsx');
    }

    private function csvDelimiter(UploadedFile $file): string
    {
        $firstLine = (string) fgets(fopen($file->getRealPath(), 'rb'));
        $delimiters = [',' => substr_count($firstLine, ','), ';' => substr_count($firstLine, ';'), "\t" => substr_count($firstLine, "\t")];
        arsort($delimiters);

        return (string) array_key_first($delimiters);
    }
}
