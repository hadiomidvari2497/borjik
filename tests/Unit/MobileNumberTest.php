<?php

namespace Tests\Unit;

use App\Support\MobileNumber;
use PHPUnit\Framework\TestCase;

class MobileNumberTest extends TestCase
{
    public function test_normalizes_common_formats(): void
    {
        foreach (['۰۹۱۲۳۴۵۶۷۸۹', '+989123456789', '00989123456789', '9123456789', '0912 345 6789', '989123456789', '0912-345-6789', '٠٩١٢٣٤٥٦٧٨٩'] as $in) {
            $this->assertSame('09123456789', MobileNumber::normalize($in), $in);
        }
    }

    public function test_rejects_invalid_numbers(): void
    {
        foreach ([null, '', 'abc', '09123', '0812345678901', '021123456'] as $in) {
            $this->assertNull(MobileNumber::normalize($in), (string) $in);
        }
    }
}
