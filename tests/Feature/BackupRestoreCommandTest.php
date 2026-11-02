<?php

namespace Tests\\Feature;

use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class BackupRestoreCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_command_requires_explicit_confirmation(): void
    {
        $this->artisan('borjik:restore', ['backupRun' => 1, '--target-database' => 'restore_test'])
            ->assertExitCode(1)
            ->expectsOutput('برای جلوگیری از بازیابی ناخواسته، گزینه --force الزامی است.');
    }
}
