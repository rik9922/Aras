<?php

namespace Tests\Unit;

use App\Services\ExpeditedPayoutCalculator;
use PHPUnit\Framework\TestCase;

class ExpeditedPayoutCalculatorTest extends TestCase
{
    public function test_it_deducts_the_fixed_fee_only_for_expedited_payouts(): void
    {
        $calculator = new ExpeditedPayoutCalculator;

        $this->assertSame([
            'original_amount' => 10.00,
            'expedited_fee' => 2.50,
            'amount' => 7.50,
        ], $calculator->calculate(10.00, true));

        $this->assertSame([
            'original_amount' => 10.00,
            'expedited_fee' => 0.00,
            'amount' => 10.00,
        ], $calculator->calculate(10.00, false));
    }
}
