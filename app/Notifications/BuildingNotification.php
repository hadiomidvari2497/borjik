<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/**
 * اعلان درون‌سامانه‌ای عمومی (بند ۴.۱۸). به ساختمان وصل است تا هر کاربر در هر ساختمان فقط اعلان‌های همان ساختمان را ببیند.
 * ارسال را از طریق App\Services\Notifier انجام دهید، نه مستقیم.
 */
class BuildingNotification extends Notification
{
    public function __construct(
        public int $buildingId,
        public string $event,
        public string $title,
        public string $message,
        public ?string $url = null,
        public string $level = 'info',      // info | success | warning
        public string $category = 'system', // invoice | payment | contract | expense | system ...
        public ?string $key = null,         // کلید جلوگیری از اعلان تکراری
        public ?string $linkLabel = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'building_id' => $this->buildingId,
            'event' => $this->event,
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'level' => $this->level,
            'category' => $this->category,
            'key' => $this->key,
            'link_label' => $this->linkLabel,
        ];
    }
}
