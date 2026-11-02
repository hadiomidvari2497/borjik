<?php

namespace App\Console\Commands;

use App\Models\SupportTicket;
use App\Support\SupportTicketIntelligence;
use Illuminate\Console\Command;

class AnalyzeExistingSupportTickets extends Command
{
    protected $signature = 'support:analyze-existing {--force : Recalculate analysis for tickets that were already analyzed}';

    protected $description = 'Analyze existing support tickets for priority suggestions and similar requests';

    public function handle(SupportTicketIntelligence $intelligence): int
    {
        $query = SupportTicket::query()->orderBy('id');
        if (! $this->option('force')) {
            $query->whereNull('priority_suggestion');
        }

        $total = (clone $query)->count();
        $this->output->progressStart($total);

        $query->chunkById(100, function ($tickets) use ($intelligence) {
            foreach ($tickets as $ticket) {
                $ticket->update($intelligence->analyze($ticket));
                $this->output->progressAdvance();
            }
        });

        $this->output->progressFinish();
        $this->info($total.' support tickets analyzed.');

        return self::SUCCESS;
    }
}
