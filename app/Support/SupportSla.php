<?php

namespace App\Support;

use Illuminate\Support\Carbon;

class SupportSla
{
    public static function deadlines(string $priority, ?Carbon $from = null): array
    {
        $targets = config('support.sla.priorities.'.$priority, config('support.sla.priorities.normal'));
        $start = $from?->copy() ?? now();

        return [
            'first_response_due_at' => $start->copy()->addMinutes((int) $targets['first_response_minutes']),
            'resolution_due_at' => $start->copy()->addMinutes((int) $targets['resolution_minutes']),
        ];
    }

    public static function resolutionDeadline(string $priority): Carbon
    {
        $targets = config('support.sla.priorities.'.$priority, config('support.sla.priorities.normal'));

        return now()->addMinutes((int) $targets['resolution_minutes']);
    }
}
