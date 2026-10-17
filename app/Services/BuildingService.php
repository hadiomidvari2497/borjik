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

    /**
     * ساختمان‌هایی را که به اشتراک منقضی‌شده‌ی سازنده وصل مانده‌اند، به اشتراک فعال جدید او منتقل می‌کند.
     * این مسیر برای ساختمان‌هایی است که پیش از اصلاح فرایند تغییر اشتراک ساخته شده‌اند.
     */
    public function ensureActiveSubscription(Building $building): ?Subscription
    {
        $building->loadMissing('subscription');

        if ($building->subscription?->isActive()) {
            return $building->subscription;
        }

        $owner = $building->createdBy;
        $subscription = $owner ? $this->activeSubscription($owner) : null;

        if (! $subscription) {
            return $building->subscription;
        }

        if ((int) $building->subscription_id !== (int) $subscription->id) {
            DB::table('buildings')
                ->where('id', $building->id)
                ->update(['subscription_id' => $subscription->id]);

            $building->subscription_id = $subscription->id;
            $building->setRelation('subscription', $subscription);
        }

        return $subscription;
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

            $this->seedStaffRoles($building->id);

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

    private function seedStaffRoles(int $buildingId): void
    {
        $templates = Role::whereNull('building_id')->whereIn('key', ['security', 'cleaning', 'maintenance'])->with('permissions')->get();
        foreach ($templates as $template) {
            $role = Role::create([
                'building_id' => $buildingId,
                'key' => $template->key,
                'name_fa' => $template->name_fa,
                'is_staff' => true,
                'is_default' => true,
                'is_active' => true,
            ]);
            $role->permissions()->sync($template->permissions->modelKeys());
        }
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
