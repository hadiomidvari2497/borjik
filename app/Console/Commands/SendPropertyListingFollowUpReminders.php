<?php

namespace App\Console\Commands;

use App\Models\PropertyListingInquiry;
use App\Notifications\PropertyListingNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendPropertyListingFollowUpReminders extends Command
{
    protected $signature = 'properties:send-follow-up-reminders';

    protected $description = 'Send one in-app notification when a property lead follow-up becomes due';

    public function handle(): int
    {
        $sent = 0;

        PropertyListingInquiry::query()
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', now())
            ->whereNull('follow_up_notified_at')
            ->where('status', '!=', 'closed')
            ->orderBy('id')
            ->chunkById(100, function ($inquiries) use (&$sent): void {
                foreach ($inquiries as $candidate) {
                    $wasSent = DB::transaction(function () use ($candidate): bool {
                        $inquiry = PropertyListingInquiry::query()
                            ->whereKey($candidate->id)
                            ->lockForUpdate()
                            ->with(['recipient', 'listing'])
                            ->first();

                        if (
                            ! $inquiry
                            || ! $inquiry->recipient
                            || ! $inquiry->follow_up_at
                            || $inquiry->follow_up_at->isFuture()
                            || $inquiry->follow_up_notified_at
                            || $inquiry->status === 'closed'
                        ) {
                            return false;
                        }

                        $listingTitle = $inquiry->listing?->title ?? 'آگهی شما';
                        $message = 'زمان پیگیری مشتری برای آگهی «'.$listingTitle.'» فرا رسیده است.';
                        if (filled($inquiry->follow_up_note)) {
                            $message .= ' یادداشت: '.$inquiry->follow_up_note;
                        }

                        $inquiry->recipient->notify(new PropertyListingNotification(
                            event: 'lead_follow_up_due',
                            title: 'یادآوری پیگیری مشتری',
                            message: $message,
                            url: route('properties.chat.show', $inquiry),
                            level: 'warning',
                            listingId: $inquiry->property_listing_id,
                        ));

                        $inquiry->forceFill(['follow_up_notified_at' => now()])->save();

                        return true;
                    });

                    if ($wasSent) {
                        $sent++;
                    }
                }
            });

        $this->info("{$sent} property lead follow-up reminders sent.");

        return self::SUCCESS;
    }
}
