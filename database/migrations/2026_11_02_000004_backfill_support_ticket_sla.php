<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('support_tickets')->orderBy('id')->chunkById(200, function ($tickets): void {
            foreach ($tickets as $ticket) {
                $targets = config('support.sla.priorities.'.$ticket->priority, config('support.sla.priorities.normal'));
                $latestCustomerReply = DB::table('support_messages')
                    ->where('support_ticket_id', $ticket->id)
                    ->where('sender_type', 'user')
                    ->max('created_at');
                $firstAdminReply = DB::table('support_messages')
                    ->where('support_ticket_id', $ticket->id)
                    ->where('sender_type', 'admin')
                    ->min('created_at');

                $responseDue = Carbon::parse($ticket->created_at)->addMinutes((int) $targets['first_response_minutes']);
                $resolutionDue = Carbon::parse($latestCustomerReply ?: $ticket->created_at)
                    ->addMinutes((int) $targets['resolution_minutes']);

                $updates = [
                    'first_response_due_at' => $responseDue,
                    'resolution_due_at' => $resolutionDue,
                    'first_response_at' => $firstAdminReply,
                ];

                if (($firstAdminReply && Carbon::parse($firstAdminReply)->gt($responseDue))
                    || (! $firstAdminReply && in_array($ticket->status, ['resolved', 'closed'], true)
                        && $ticket->closed_at && Carbon::parse($ticket->closed_at)->gt($responseDue))) {
                    $updates['first_response_breached_at'] = $responseDue;
                }

                if (in_array($ticket->status, ['resolved', 'closed'], true)
                    && $ticket->closed_at && Carbon::parse($ticket->closed_at)->gt($resolutionDue)) {
                    $updates['resolution_breached_at'] = $resolutionDue;
                }

                DB::table('support_tickets')->where('id', $ticket->id)->update($updates);
            }
        });
    }

    public function down(): void
    {
        // Backfilled timestamps are operational history and are intentionally retained.
    }
};
