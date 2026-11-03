<?php

namespace Tests\Unit;

use App\Models\Event;
use App\Http\Controllers\Concerns\NormalizesBookedByEmails;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookedByEmailsNormalizerHarness
{
    use NormalizesBookedByEmails;

    public function normalize(mixed $value): array
    {
        return $this->normalizeBookedByEmails($value);
    }
}

class BookedByEmailsTest extends TestCase
{
    #[Test]
    public function normalizer_accepts_arrays_json_and_delimited_strings(): void
    {
        $normalizer = new BookedByEmailsNormalizerHarness();

        self::assertSame(
            ['first@example.com', 'second@example.com'],
            $normalizer->normalize([' FIRST@example.com ', '', 'second@example.com'])
        );
        self::assertSame(
            ['first@example.com', 'second@example.com'],
            $normalizer->normalize('[" FIRST@example.com ", "second@example.com"]')
        );
        self::assertSame(
            ['first@example.com', 'second@example.com', 'third@example.com'],
            $normalizer->normalize(" FIRST@example.com; second@example.com\nthird@example.com ")
        );
        self::assertSame(
            ['a@b.com'],
            $normalizer->normalize(['A@b.com', 'a@b.com', ' A@B.COM '])
        );
    }

    #[Test]
    public function booked_by_display_supports_email_arrays_and_legacy_values(): void
    {
        self::assertSame(
            'first@example.com, second@example.com',
            Event::make(['booked_by' => '["first@example.com", "second@example.com"]'])->booked_by_display
        );
        self::assertSame(
            'First Name, Second Name',
            Event::make(['booked_by' => '["First Name", "Second Name"]'])->booked_by_display
        );
        self::assertSame(
            'Legacy Name',
            Event::make(['booked_by' => 'Legacy Name'])->booked_by_display
        );
        self::assertSame('', Event::make(['booked_by' => null])->booked_by_display);
    }
}
