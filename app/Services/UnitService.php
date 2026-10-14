<?php

namespace App\Services;

use App\Exceptions\SubscriptionException;
use App\Models\Building;
use App\Models\Subscription;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;

class UnitService
{
    public function __construct(private readonly SubscriptionLimit $limit)
    {
    }

    /**
     * ساخت یک یا چند واحد با کنترل سقف اشتراک. ردیف اشتراک قفل می‌شود تا دو درخواست هم‌زمان
     * از سقف عبور نکنند. یا همه‌ی واحدها ساخته می‌شوند یا هیچ‌کدام.
     *
     * @param array<int, array<string, mixed>> $rows
     */
    public function createMany(Building $building, array $rows): int
    {
        return DB::transaction(function () use ($building, $rows) {
            $subscription = $building->subscription_id ? Subscription::lockForUpdate()->find($building->subscription_id) : null;

            if (! $subscription || ! $subscription->isActive()) {
                throw new SubscriptionException('اشتراک فعالی برای این ساختمان وجود ندارد.');
            }
            if (! $this->limit->canAddUnits($subscription, count($rows))) {
                throw new SubscriptionException(sprintf(
                    'سقف اشتراک (%d واحد) پر است؛ فقط %d واحد دیگر قابل افزودن است.',
                    $subscription->units_limit,
                    $this->limit->remaining($subscription),
                ));
            }

            foreach ($rows as $row) {
                Unit::create($row);
            }

            return count($rows);
        });
    }
}
