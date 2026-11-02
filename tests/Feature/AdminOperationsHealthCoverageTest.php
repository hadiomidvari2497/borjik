<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\OperationsHealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOperationsHealthCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_check_records_email_gateway_queue_and_security_checks(): void
    {
        AdminUser::create([
            'username' => 'health-super-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
            'two_factor_enabled' => false,
        ]);

        $this->artisan('borjik:health-check')->assertExitCode(1);

        $record = OperationsHealthCheck::query()->latest('id')->firstOrFail();
        $this->assertArrayHasKey('email', $record->checks);
        $this->assertArrayHasKey('payment_gateway', $record->checks);
        $this->assertArrayHasKey('queue', $record->checks);
        $this->assertArrayHasKey('admin_security', $record->checks);
        $this->assertSame('warning', $record->checks['admin_security']['status']);
        $this->assertSame('warning', $record->checks['email']['status']);
        $this->assertContains($record->checks['payment_gateway']['status'], ['warning', 'fail']);
        $this->assertContains($record->checks['sms']['status'], ['warning', 'fail']);
        $this->assertContains($record->checks['queue']['status'], ['warning', 'unknown']);
        $this->assertContains($record->status, ['warning', 'fail']);
        $this->assertArrayHasKey('backup', $record->checks);
        $this->assertStringContainsString('مدیر فعال بدون تأیید دومرحله‌ای', $record->checks['admin_security']['detail']);
    }
}
