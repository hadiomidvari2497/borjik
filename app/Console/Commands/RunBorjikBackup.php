<?php

namespace App\Console\Commands;

use App\Models\BackupRun;
use App\Models\OperationsSetting;
use App\Services\Operations\BackupService;
use Illuminate\Console\Command;

class RunBorjikBackup extends Command
{
    protected $signature = 'borjik:backup {--force : Run even if the configured schedule is not due}';
    protected $description = 'Create a local Borjik backup and optionally copy it to Google Drive';

    public function handle(BackupService $backups): int
    {
        $settings = OperationsSetting::current()->values();
        if (! $settings['backup_enabled'] && ! $this->option('force')) {
            $this->info('Automatic backups are disabled in Operations Center.');
            return self::SUCCESS;
        }

        $last = BackupRun::query()->latest('id')->first();
        $frequency = $settings['backup_frequency'] ?? 'daily';
        $hours = $frequency === 'weekly' ? 168 : ($frequency === 'twice_daily' ? 12 : 24);
        if (! $this->option('force') && $last && $last->status === 'success' && $last->finished_at && $last->finished_at->gt(now()->subHours($hours))) {
            $this->info('A successful backup already exists in the configured period.');
            return self::SUCCESS;
        }

        $run = $backups->run();
        $this->line(($run->status === 'success' ? 'SUCCESS: ' : 'FAILED: ').($run->message ?? ''));
        return $run->status === 'success' ? self::SUCCESS : self::FAILURE;
    }
}
