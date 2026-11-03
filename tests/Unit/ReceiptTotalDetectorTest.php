<?php

namespace Tests\Unit;

use App\Services\ReceiptTotalDetector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ReceiptTotalDetectorTest extends TestCase
{
    #[Test]
    public function it_prefers_a_labeled_total_over_other_amounts(): void
    {
        $text = "ITEM 19.99\nSUBTOTAL 19.99\nTAX 1.60\nTOTAL \$21.59";

        $this->assertSame('21.59', (new ReceiptTotalDetector)->detect($text));
    }

    #[Test]
    public function it_uses_the_largest_amount_when_no_total_label_is_readable(): void
    {
        $text = "ITEM A 4.50\nITEM B 8.25\n\$13.80";

        $this->assertSame('13.80', (new ReceiptTotalDetector)->detect($text));
    }

    #[Test]
    public function it_returns_null_when_no_amount_is_present(): void
    {
        $this->assertNull((new ReceiptTotalDetector)->detect('Thank you for shopping with us'));
    }

    #[Test]
    public function it_supports_comma_decimal_amounts(): void
    {
        $this->assertSame('1,234.56', (new ReceiptTotalDetector)->detect('GRAND TOTAL 1.234,56'));
    }
}
