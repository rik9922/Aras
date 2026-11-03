<?php

namespace App\Services;

class ExpeditedPayoutCalculator
{
    public const PROCESSING_FEE = 2.50;

    /**
     * Return the amounts persisted for an ambassador payout. `amount` is always
     * the amount sent to Dwolla; the original amount is retained for reporting.
     *
     * @return array{original_amount: float, expedited_fee: float, amount: float}
     */
    public function calculate(float $originalAmount, bool $isExpedited): array
    {
        $originalAmount = round($originalAmount, 2);
        $fee = $isExpedited ? self::PROCESSING_FEE : 0.00;

        return [
            'original_amount' => $originalAmount,
            'expedited_fee' => $fee,
            'amount' => round($originalAmount - $fee, 2),
        ];
    }
}
