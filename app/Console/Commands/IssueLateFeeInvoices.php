<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class IssueLateFeeInvoices extends Command
{
    protected $signature = 'charges:issue-late-fees';
    protected $description = 'Issue overdue late-fee invoices';

    public function handle(): int
    {
        return self::SUCCESS;
    }
}
