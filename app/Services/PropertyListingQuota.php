<?php

namespace App\Services;

use App\Models\PropertyListing;
use App\Models\PropertyListingCreditTransaction;
use App\Models\SiteSetting;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PropertyListingQuota
{
    public function freeLimit(): int
    {
        return max(0, (int) SiteSetting::get('property_listings.free_limit', 3));
    }

    public function availableCredits(int $userId): int
    {
        return max(0, (int) PropertyListingCreditTransaction::query()
            ->where('user_id', $userId)->sum('quantity'));
    }

    public function hasUnlimitedAccess(int $userId): bool
    {
        return Subscription::query()->where('user_id', $userId)
            ->where('status', 'active')->whereNotNull('expires_at')
            ->where('expires_at', '>', now())->exists();
    }

    /**
     * Reserve a purchased credit only when the account has exhausted its free slots.
     * Caller must create the listing in the same database transaction.
     */
    public function consumeForNewListing(User $user): ?PropertyListingCreditTransaction
    {
        return DB::transaction(function () use ($user) {
            User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($this->hasUnlimitedAccess($user->id)) {
                return null;
            }

            $activeCount = PropertyListing::query()
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending', 'active'])
                ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                ->count();
            if ($activeCount < $this->freeLimit()) {
                return null;
            }

            $credits = $this->availableCredits($user->id);
            if ($credits < 1) {
                abort(403, 'سهمیه آگهی رایگان شما تکمیل شده است. برای انتشار آگهی جدید یک بسته آگهی تهیه کنید.');
            }

            return PropertyListingCreditTransaction::create([
                'user_id' => $user->id,
                'quantity' => -1,
                'type' => 'consume',
                'description' => 'مصرف اعتبار برای انتشار آگهی',
            ]);
        });
    }

    public function consumeForRenewal(User $user, PropertyListing $listing): ?PropertyListingCreditTransaction
    {
        User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

        if ($this->hasUnlimitedAccess($user->id)) {
            return null;
        }

        if ($this->availableCredits($user->id) < 1) {
            abort(403, 'برای تمدید آگهی به اعتبار نیاز دارید. ابتدا یک بسته آگهی تهیه کنید.');
        }

        return PropertyListingCreditTransaction::create([
            'user_id' => $user->id,
            'property_listing_id' => $listing->id,
            'quantity' => -1,
            'type' => 'renewal',
            'description' => 'مصرف اعتبار برای تمدید آگهی',
        ]);
    }

    public function grantPaidOrder(int $orderId): void
    {
        $order = \App\Models\PropertyListingPackageOrder::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
        if ($order->status !== 'paid') {
            throw new \RuntimeException('Package order must be paid before granting credits.');
        }

        $exists = PropertyListingCreditTransaction::query()
            ->where('package_order_id', $order->id)->where('type', 'purchase')->exists();
        if ($exists) {
            return;
        }

        PropertyListingCreditTransaction::create([
            'user_id' => $order->user_id,
            'package_order_id' => $order->id,
            'quantity' => $order->quantity,
            'type' => 'purchase',
            'description' => 'خرید بسته '.$order->quantity.' آگهی',
        ]);
    }
}