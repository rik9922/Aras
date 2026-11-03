<?php

namespace App\Services;

use thiagoalessio\TesseractOCR\TesseractOCR;
use Throwable;

class ReceiptOcrService
{
    public function __construct(private readonly ReceiptTotalDetector $totalDetector)
    {
    }

    public function read(string $filePath): array
    {
        $text = (new TesseractOCR($filePath))
            ->executable($this->executable())
            ->lang('eng')
            ->psm(6)
            ->run(30);

        return [
            'text' => $text,
            'total' => $this->totalDetector->detect($text),
        ];
    }

    public function status(): array
    {
        try {
            $version = (new TesseractOCR)
                ->executable($this->executable())
                ->version();

            return [
                'available' => true,
                'message' => 'Tesseract '.$version.' is available.',
            ];
        } catch (Throwable) {
            return [
                'available' => false,
                'message' => 'Tesseract is not available to the web process. Install the Tesseract binary and English language data, or set TESSERACT_PATH to its executable path.',
            ];
        }
    }

    private function executable(): string
    {
        return (string) config('services.tesseract.path', 'tesseract');
    }

}
