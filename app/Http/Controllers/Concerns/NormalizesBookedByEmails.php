<?php

namespace App\Http\Controllers\Concerns;

trait NormalizesBookedByEmails
{
    protected function normalizeBookedByEmails($raw): array
    {
        if (is_array($raw)) {
            $items = $raw;
        } else {
            $value = trim((string) $raw);
            $decoded = json_decode($value, true);
            $items = is_array($decoded) ? $decoded : preg_split('/[,;\r\n]+/', $value);
        }

        $emails = array_values(array_filter(
            array_map(fn ($email) => strtolower(trim((string) $email)), $items),
            fn ($email) => $email !== ''
        ));

        return array_values(array_unique($emails));
    }
}
