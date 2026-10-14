<?php

namespace Tests\Unit;

use App\Support\Totp;
use PHPUnit\Framework\TestCase;

/** بردارهای رسمی RFC 6238 (SHA-1) با کلید ASCII «12345678901234567890». */
class TotpTest extends TestCase
{
    private const SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public function test_rfc6238_vectors(): void
    {
        $this->assertSame('94287082', Totp::code(self::SECRET, 59, 8));
        $this->assertSame('287082', Totp::code(self::SECRET, 59, 6));
        $this->assertSame('081804', Totp::code(self::SECRET, 1111111109));
        $this->assertSame('005924', Totp::code(self::SECRET, 1234567890));
    }

    public function test_verify_accepts_neighbouring_window_only(): void
    {
        $t = 1234567890;
        $this->assertTrue(Totp::verify(self::SECRET, '005924', 1, $t));
        $this->assertTrue(Totp::verify(self::SECRET, '005924', 1, $t + 30));
        $this->assertFalse(Totp::verify(self::SECRET, '005924', 1, $t + 90));
        $this->assertFalse(Totp::verify(self::SECRET, '000000', 1, $t));
    }

    public function test_base32_roundtrip_and_secret_length(): void
    {
        $raw = random_bytes(20);
        $this->assertSame($raw, Totp::base32Decode(Totp::base32Encode($raw)));
        $this->assertSame(32, strlen(Totp::generateSecret()));
    }
}
