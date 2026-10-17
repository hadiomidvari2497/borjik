<?php

namespace App\Console\Commands;

use App\Models\Building;
use App\Models\CalendarEvent;
use App\Notifications\CalendarEventReminderNotification;
use App\Support\CurrentBuilding;
use Illuminate\Console\Command;

class SendCalendarReminders extends Command
{
    protected $signature = 'calendar:send-reminders';

    protected $description = 'Send in-app reminders for upcoming calendar events';

    public function handle(CurrentBuilding $current): int
    {
        $sent = 0;

        Building::query()->where('status', 'active')->orderBy('id')->each(function (Building $building) use ($current, &$sent) {
            $current->actingAs((int) $building->id, function () use ($building, &$sent) {
                CalendarEvent::query()
                    ->whereNotNull('reminder_minutes')
                    ->whereNull('reminder_sent_at')
                    ->where('starts_at', '<=', now()->addDays(7))
                    ->where('starts_at', '>', now())
                    ->with('creator')
                    ->orderBy('starts_at')
                    ->chunkById(100, function ($events) use ($building, &$sent) {
                        foreach ($events as $event) {
                            if (! $event->creator || $event->starts_at->copy()->subMinutes((int) $event->reminder_minutes)->isFuture()) {
                                continue;
                            }

                            $event->creator->notify(new CalendarEventReminderNotification(
                                buildingId: (int) $building->id,
                                eventId: (int) $event->id,
                                eventTitle: $event->title,
                                message: 'رویداد «'.$event->title.'» به‌زودی آغاز می‌شود.',
                            ));
                            $event->forceFill(['reminder_sent_at' => now()])->save();
                            $sent++;
                        }
                    });
            });
        });

        $this->info("{$sent} calendar reminders sent.");

        return self::SUCCESS;
    }
}
