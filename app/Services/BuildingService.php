<?php

namespace App\Services;

use App\Exceptions\SubscriptionException;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\ChargeSetting;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\User;
use App\Support\CurrentBuilding;
use Illuminate\Support\Facades\DB;

class BuildingService
{
    /** هزینه‌های پیش‌فرض هر ساختمان با مبنای تقسیم پیشنهادی (روش ۱۰ شارژ). */
    private const DEFAULT_CATEGORIES = [
        ['آب', 'variable', 'persons'], ['برق مشترک', 'variable', 'equal'], ['گاز', 'variable', 'area'],
        ['آسانسور', 'fixed', 'equal'], ['نظافت', 'fixed', 'equal'], ['تعمیرات', 'variable', 'equal'],
    ];

    public function activeSubscription(User $user): ?Subscription
    {
        return Subscription::where('user_id', $user->id)->where('status', 'active')
            ->where('expires_at', '>', now())->orderByDesc('expires_at')->first();
    }

    /** ساخت ساختمان: سازنده «مالک ساختمان» می‌شود و به اشتراک فعال خودش وصل می‌گردد. */
    public function create(User $user, array $data): Building
    {
        $subscription = $this->activeSubscription($user)
            ?? throw new SubscriptionException('برای ساخت ساختمان ابتدا باید اشتراک فعال داشته باشید.');

        return DB::transaction(function () use ($user, $data, $subscription) {
            $building = Building::create($data + [
                'code' => $this->uniqueCode(),
                'created_by' => $user->id,
                'subscription_id' => $subscription->id,
                'status' => 'active',
            ]);

            BuildingUserRole::create([
                'building_id' => $building->id,
                'user_id' => $user->id,
                'role_id' => Role::where('key', 'owner')->value('id'),
                'status' => 'active',
                'joined_at' => now(),
            ]);

            app(CurrentBuilding::class)->actingAs($building->id, function () {
                ChargeSetting::create([]); // مقادیر پیش‌فرض ستون‌ها
                foreach (self::DEFAULT_CATEGORIES as [$name, $kind, $basis]) {
                    ExpenseCategory::create(['name' => $name, 'kind' => $kind, 'split_basis' => $basis]);
                }
            });

            return $building;
        });
    }

    private function uniqueCode(): string
    {
        for ($i = 0; $i < 20; $i++) {
            $code = (string) random_int(10000, 99999);
            if (! Building::where('code', $code)->exists()) {
                return $code;
            }
        }
        throw new \RuntimeException('ساخت کد یکتا برای ساختمان ممکن نشد.');
    }
}
