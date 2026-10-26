<?php

namespace App\Services\Mailbox;

use App\Models\MailAccount;
use Symfony\Component\Process\Process;
use RuntimeException;

final class MailboxProvisioner
{
    public function provision(string $email, string $password): void
    {
        $this->run(['action' => 'provision', 'email' => $email, 'password' => $password]);
    }

    public function remove(MailAccount $account): void
    {
        $this->run(['action' => 'delete', 'email' => $account->email]);
    }

    private function run(array $payload): void
    {
        $binary = '/usr/local/sbin/sabkhoneh-mailbox';

        // The helper is intentionally root-owned and not executable by www-data
        // directly. Check that it exists as a regular file, then let the narrowly
        // scoped sudoers rule authorize its execution as root.
        if (! is_file($binary) || is_link($binary)) {
            throw new RuntimeException('ابزار امن مدیریت صندوق روی سرور نصب نشده است؛ راهنمای docs/mailbox-server-setup.md را اجرا کنید.');
        }

        $process = new Process(['sudo', '-n', $binary]);
        $process->setInput(json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            report(new RuntimeException('Mailbox provisioning helper failed: '.trim($process->getErrorOutput())));
            throw new RuntimeException('تغییر صندوق روی سرور انجام نشد. گزارش خطای سیستم را بررسی کنید.');
        }
    }
}
