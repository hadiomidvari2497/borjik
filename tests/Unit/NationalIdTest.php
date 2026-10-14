<?php

namespace Tests\Unit;

use App\Rules\IranianNationalId;
use PHPUnit\Framework\TestCase;

class NationalIdTest extends TestCase
{
    public function test_national_code(): void
    {
        foreach (['0499370899', '0084575948', '0013542419'] as $ok) {
            $this->assertTrue(IranianNationalId::isValidNationalCode($ok), $ok);
        }
        foreach (['1234567890', '1111111111', '049937089', '04993708990', 'abcdefghij'] as $bad) {
            $this->assertFalse(IranianNationalId::isValidNationalCode($bad), $bad);
        }
    }

    public function test_legal_id(): void
    {
        $this->assertTrue(IranianNationalId::isValidLegalId('10380284790'));
        $this->assertTrue(IranianNationalId::isValidLegalId('14003778990'));
        $this->assertFalse(IranianNationalId::isValidLegalId('10380284791'));
        $this->assertFalse(IranianNationalId::isValidLegalId('11111111111'));
    }
}
