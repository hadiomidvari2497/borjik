<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CalendarEventReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        public int $buildingId,
        public int $eventId,
        public string $eventTitle,
        public string $message,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'building_id' => $this->buildingId,
            'event_id' => $this->eventId,
            'event' => 'calendar_reminder',
            'title' => 'یادآوری رویداد تقویم',
            'message' => $this->message,
            'url' => route('events.index'),
        ];
    }
}
