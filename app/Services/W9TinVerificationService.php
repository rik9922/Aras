<?php

namespace App\Services;

class W9TinVerificationService
{
    /**
     * Strict local SSN validation (IRS/SSA known-invalid ranges).
     *
     * @return array{ok:bool,error?:string}
     */
    public function validateSsn(string $digits): array
    {
        if (! preg_match('/^\d{9}$/', $digits)) {
            return ['ok' => false, 'error' => 'SSN must be exactly 9 digits.'];
        }

        $area = substr($digits, 0, 3);
        $group = substr($digits, 3, 2);
        $serial = substr($digits, 5, 4);

        // Invalid area numbers: 000, 666, 900-999
        $areaInt = (int) $area;
        if ($area === '000' || $area === '666' || $areaInt >= 900) {
            return ['ok' => false, 'error' => 'SSN appears invalid. Please re-check the first 3 digits.'];
        }

        if ($group === '00') {
            return ['ok' => false, 'error' => 'SSN appears invalid. Please re-check the middle 2 digits.'];
        }

        if ($serial === '0000') {
            return ['ok' => false, 'error' => 'SSN appears invalid. Please re-check the last 4 digits.'];
        }

        return ['ok' => true];
    }

    /**
     * Strict local EIN validation (format-only).
     *
     * @return array{ok:bool,error?:string}
     */
    public function validateEin(string $digits): array
    {
        if (! preg_match('/^\d{9}$/', $digits)) {
            return ['ok' => false, 'error' => 'EIN must be exactly 9 digits.'];
        }

        // First two digits (prefix) cannot be 00
        if (substr($digits, 0, 2) === '00') {
            return ['ok' => false, 'error' => 'EIN appears invalid. Please re-check the first 2 digits.'];
        }

        // All zeros is invalid
        if ($digits === '000000000') {
            return ['ok' => false, 'error' => 'EIN appears invalid.'];
        }

        return ['ok' => true];
    }

    /**
     * Optional external verification hook.
     * Currently returns local validation only, unless enabled later.
     *
     * @return array{ok:bool,provider:string,error?:string}
     */
    public function verify(string $digits, bool $isEin): array
    {
        // Always perform local validation first.
        $local = $isEin ? $this->validateEin($digits) : $this->validateSsn($digits);
        if (! ($local['ok'] ?? false)) {
            return [
                'ok' => false,
                'provider' => 'basic',
                'error' => $local['error'] ?? 'Invalid Tax ID.',
            ];
        }

        if (! (bool) config('w9.verification.enabled')) {
            return ['ok' => true, 'provider' => 'basic'];
        }

        // Placeholder for future provider integration.
        // Keep behavior safe by not blocking if provider is not implemented.
        return ['ok' => true, 'provider' => (string) config('w9.verification.provider', 'basic')];
    }
}

