<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class PropertyListingNotification extends Notification
{
    public function __construct(
        public string $event,
        public string $title,
        public string $message,
        public string $url,
        public string $level = 'info',
        public ?int $listingId = null,
        public ?int $reportId = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'category' => 'marketplace',
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'level' => $this->level,
            'listing_id' => $this->listingId,
            'report_id' => $this->reportId,
        ];
    }
}