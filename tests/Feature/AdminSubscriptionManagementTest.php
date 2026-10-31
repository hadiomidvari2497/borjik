<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Building;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSubscriptionManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = [], bool $super = false): AdminUser
    {
        static $n = 0;
        $n++;
        $admin = AdminUser::create([
            'username' => 'subadmin'.$n,
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => $super,
        ]);

        if ($permissions !== []) {
            $role = AdminRole::create(['name' => 'subrole'.$n]);
            $role->syncPermissions($permissions);
            $admin->roles()->attach($role->id);
        }

        return $admin;
    }

    private function user(string $mobile): User
    {
        return User::create(['mobile' => $mobile, 'first_name' => 'آزمایش', 'last_name' => 'کاربر']);
    }

    public function test_admin_with_subscription_permission_can_grant_a_plan_and_expire_old_subscription(): void
    {
        $admin = $this->admin(['subscriptions.manage']);
        $user = $this->user('09120000001');
        $oldPlan = SubscriptionPlan::create(['name' => 'قدیمی', 'units_limit' => 10, 'duration_months' => 1, 'price' => 0, 'is_active' => true]);
        $newPlan = SubscriptionPlan::create(['name' => 'ویژه', 'units_limit' => 100, 'duration_months' => 6, 'price' => 1000, 'is_active' => false]);
        $building = Building::create(['name' => 'ساختمان آزمایشی', 'code' => '10001', 'created_by' => $user->id]);
        $old = Subscription::create([
            'user_id' => $user->id, 'building_id' => $building->id, 'plan_id' => $oldPlan->id, 'units_limit' => 10,
            'starts_at' => now()->subDays(2), 'expires_at' => now()->addDays(10), 'status' => 'active',
        ]);
        $building->update(['subscription_id' => $old->id]);

        $this->actingAs($admin, 'admin')->post(route('admin.subscriptions.store'), [
            'building_id' => $building->id, 'plan_id' => $newPlan->id,
        ])->assertRedirect(route('admin.subscriptions.index'))
          ->assertSessionHas('status');

        $this->assertSame('expired', $old->fresh()->status);
        $this->assertDatabaseHas('subscriptions', [
            'user_id' => $user->id, 'building_id' => $building->id, 'plan_id' => $newPlan->id,
            'units_limit' => 100, 'status' => 'active',
        ]);
        $new = Subscription::where('building_id', $building->id)->where('plan_id', $newPlan->id)->firstOrFail();
        $this->assertTrue($new->expires_at->isAfter(now()->addMonths(5)));
    }

    public function test_admin_without_subscription_permission_cannot_view_or_grant_subscriptions(): void
    {
        $admin = $this->admin(['plans.manage']);
        $user = $this->user('09120000002');
        $plan = SubscriptionPlan::create(['name' => 'تست', 'units_limit' => 10, 'duration_months' => 1, 'price' => 0, 'is_active' => true]);

        $this->actingAs($admin, 'admin')->get(route('admin.subscriptions.index'))->assertForbidden();
        $building = Building::create(['name' => 'ساختمان محروم', 'code' => '10002', 'created_by' => $user->id]);
        $this->actingAs($admin, 'admin')->post(route('admin.subscriptions.store'), [
            'building_id' => $building->id, 'plan_id' => $plan->id,
        ])->assertForbidden();

        $this->assertDatabaseMissing('subscriptions', ['building_id' => $building->id]);
    }

    public function test_subscription_management_pages_render_for_authorized_admin(): void
    {
        $admin = $this->admin([], true);
        $user = $this->user('09120000003');

        $this->actingAs($admin, 'admin')->get(route('admin.subscriptions.index'))
            ->assertOk()->assertSee($user->mobile);
        $building = Building::create(['name' => 'ساختمان صفحه', 'code' => '10003', 'created_by' => $user->id]);
        $this->actingAs($admin, 'admin')->get(route('admin.subscriptions.create', ['building_id' => $building->id]))
            ->assertOk()->assertSee('اختصاص اشتراک به ساختمان');
    }
}
