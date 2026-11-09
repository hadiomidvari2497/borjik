<?php

namespace App\\Console\\Commands;

use App\\Models\\PropertyListing;
use App\\Notifications\\PropertyListingNotification;
use Illuminate\\Console\\Command;
use Illuminate\\Support\\Facades\\DB;

class ExpirePropertyListings extends Command
{
    protected $signature = 'properties:expire-listings';

    protected $description = 'Expire published property listings whose validity period has ended';

    public function handle(): int
    {
        $expired = 0;

        PropertyListing::query()
            ->where('status', 'active')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->orderBy('id')
            ->chunkById(100, function ($listings) use (&$expired) {
                foreach ($listings as $listing) {
                    DB::transaction(function () use ($listing, &$expired) {
                        $locked = PropertyListing::query()->with('user')->whereKey($listing->id)->lockForUpdate()->first();
                        if (! $locked || $locked->status !== 'active' || ! $locked->expires_at || $locked->expires_at->isFuture()) {
                            return;
                        }

                        $locked->update(['status' => 'expired']);
                        $expired++;

                        try {
                            $locked->user?->notify(new PropertyListingNotification(
                                'listing_expired',
                                'مهلت انتشار آگهی به پایان رسید',
                                'مهلت انتشار آگهی «'.$locked->title.'» به پایان رسیده است. برای نمایش دوباره آن، از بخش آگهی‌های من تمدیدش کنید.',
                                route('properties.mine'),
                                'warning',
                                $locked->id
                            ));
                        } catch (\\Throwable $exception) {
                            report($exception);
                        }
                    });
                }
            });

        $this->info($expired.' property listings expired.');

        return self::SUCCESS;
    }
}
