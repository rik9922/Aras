<?php

namespace App\Services;

class ReceiptTotalDetector
{
    private const AMOUNT_PATTERN = '/(?<!\d)(?:[$€£]\s*)?((?:\d{1,3}(?:[,.]\d{3})+|\d+)[.,]\d{2})(?!\d)/u';

    public function detect(string $text): ?string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: [])));

        foreach (array_reverse($lines) as $line) {
            $normalized = strtolower($line);

            if (str_contains($normalized, 'subtotal') || str_contains($normalized, 'sub-total')) {
                continue;
            }

            if (preg_match('/\b(grand\s+total|amount\s+due|balance\s+due|total\s+due|total)\b/i', $line)
                && ($amount = $this->lastAmount($line)) !== null) {
                return $amount;
            }
        }

        $amounts = [];
        foreach ($lines as $line) {
            preg_match_all(self::AMOUNT_PATTERN, $line, $matches);
            foreach ($matches[1] ?? [] as $amount) {
                $amounts[] = $this->normalize($amount);
            }
        }

        if ($amounts === []) {
            return null;
        }

        usort($amounts, fn (string $left, string $right) => (float) str_replace(',', '', $right) <=> (float) str_replace(',', '', $left));

        return $amounts[0];
    }

    private function lastAmount(string $line): ?string
    {
        preg_match_all(self::AMOUNT_PATTERN, $line, $matches);
        $amount = end($matches[1]);

        return $amount === false ? null : $this->normalize($amount);
    }

    private function normalize(string $amount): string
    {
        $decimalSeparator = strrpos($amount, ',') > strrpos($amount, '.') ? ',' : '.';
        $normalized = $decimalSeparator === ','
            ? str_replace(['.', ','], ['', '.'], $amount)
            : str_replace(',', '', $amount);

        return number_format((float) $normalized, 2, '.', ',');
    }
}
