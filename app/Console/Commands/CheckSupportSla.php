<?php

namespace App\Console\Commands;

use App\Models\AdminUser;
use App\Models\SupportTicket;
use App\Notifications\SupportTicketNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckSupportSla extends Command
{
    protected $signature = 'support:check-sla';

    protected $description = 'Notify support admins about upcoming and breached support SLAs';

    public function handle(): int
    {
        $ticketIds = SupportTicket::query()
            ->whereIn('status', ['open', 'in_progress'])
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('first_response_at')
                        ->whereNull('first_response_breached_at')
                        ->whereNotNull('first_response_due_at')
                        ->where('first_response_due_at', '<=', now()->addMinutes((int) config('support.sla.warning_minutes', 30)));
                })->orWhere(function ($q) {
                    $q->whereNull('resolution_breached_at')
                        ->whereNotNull('resolution_due_at')
                        ->where('resolution_due_at', '<=', now()->addMinutes((int) config('support.sla.warning_minutes', 30)));
                });
            })
            ->orderBy('id')
            ->pluck('id');

        $sent = 0;
        foreach ($ticketIds as $ticketId) {
            $sent += $this->processTicket((int) $ticketId);
        }

        $this->info($sent.' support SLA notifications sent.');

        return self::SUCCESS;
    }

    private function processTicket(int $ticketId): int
    {
        return DB::transaction(function () use ($ticketId) {
            $ticket = SupportTicket::query()->lockForUpdate()->find($ticketId);
            if (! $ticket || ! in_array($ticket->status, ['open', 'in_progress'], true)) {
                return 0;
            }

            $now = now();
            $warningMinutes = (int) config('support.sla.warning_minutes', 30);
            $events = [];

            if (! $ticket->first_response_at && $ticket->first_response_due_at) {
                if ($ticket->first_response_due_at->lte($now) && ! $ticket->first_response_breached_at) {
                    $ticket->first_response_breached_at = $now;
                    $events[] = ['مهلت پاسخ اولیه تمام شد', 'مهلت اولین پاسخ به تیکت '.$ticket->number.' به پایان رسیده است.', 'first_response_breached'];
                } elseif ($ticket->first_response_due_at->lte($now->copy()->addMinutes($warningMinutes)) && ! $ticket->first_response_warning_sent_at) {
                    $ticket->first_response_warning_sent_at = $now;
                    $events[] = ['مهلت پاسخ اولیه نزدیک است', 'کمتر از '.\App\Support\Fa::num($warningMinutes).' دقیقه تا پایان مهلت پاسخ اولیه تیکت '.$ticket->number.' باقی مانده است.', 'first_response_warning'];
                }
            }

            if ($ticket->resolution_due_at) {
                if ($ticket->resolution_due_at->lte($now) && ! $ticket->resolution_breached_at) {
                    $ticket->resolution_breached_at = $now;
                    $events[] = ['مهلت حل تیکت تمام شد', 'مهلت رسیدگی و حل تیکت '.$ticket->number.' به پایان رسیده است.', 'resolution_breached'];
                } elseif ($ticket->resolution_due_at->lte($now->copy()->addMinutes($warningMinutes)) && ! $ticket->resolution_warning_sent_at) {
                    $ticket->resolution_warning_sent_at = $now;
                    $events[] = ['مهلت حل تیکت نزدیک است', 'کمتر از '.\App\Support\Fa::num($warningMinutes).' دقیقه تا پایان مهلت رسیدگی به تیکت '.$ticket->number.' باقی مانده است.', 'resolution_warning'];
                }
            }

            if ($events === []) {
                return 0;
            }

            $ticket->save();
            $admins = $ticket->assignedAdmin && $ticket->assignedAdmin->isActive() && $ticket->assignedAdmin->hasPermission('support.manage')
                ? collect([$ticket->assignedAdmin])
                : AdminUser::query()->where('status', 'active')->get()->filter(fn (AdminUser $admin) => $admin->hasPermission('support.manage'));

            foreach ($events as [$title, $message, $event]) {
                foreach ($admins as $admin) {
                    $admin->notify(new SupportTicketNotification($ticket, $title, $message, route('admin.support.show', $ticket), $event));
                }
            }

            return count($events) * $admins->count();
        });
    }
}
