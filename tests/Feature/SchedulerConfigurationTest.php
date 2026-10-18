<?php

namespace Tests\\Feature;

use Illuminate\\Console\\Scheduling\\Schedule;
use Tests\\TestCase;

class SchedulerConfigurationTest extends TestCase
{
    public function test_all_expected_cron_commands_are_registered(): void
    {
        $events = app(Schedule::class)->events();
        $commands = collect($events)
            ->pluck('command')
            ->filter()
            ->implode("\\n");

        foreach ([
            'rent:issue-invoices',
            'charges:remind-expenses',
            'requests:notify-overdue',
            'charges:issue-late-fees',
            'calendar:send-reminders',
        ] as $command) {
            $this->assertStringContainsString($command, $commands, "Missing scheduled command: {$command}");
        }
    }

    public function test_minute_level_jobs_have_a_bounded_overlap_lock(): void
    {
        $events = app(Schedule::class)->events();

        $calendar = collect($events)->first(
            fn ($event) => str_contains((string) ($event->command ?? ''), 'calendar:send-reminders')
        );

        $heartbeat = collect($events)->first(
            fn ($event) => ($event->description ?? null) === 'scheduler:heartbeat'
        );

        $this->assertNotNull($calendar, 'Calendar reminders must be scheduled.');
        $this->assertNotNull($heartbeat, 'Scheduler heartbeat must be scheduled.');
        $this->assertSame('* * * * *', $calendar->expression);
        $this->assertSame('* * * * *', $heartbeat->expression);
    }
}
