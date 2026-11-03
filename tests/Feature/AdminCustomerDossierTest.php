<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCustomerDossierTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions): AdminUser
    {
        static $index = 0;
        $index++;
        $admin = AdminUser::create([
            'username' => 'dossier-admin-'.$index,
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => false,
        ]);
        $role = AdminRole::create(['name' => 'dossier-role-'.$index]);
        $role->syncPermissions($permissions);
        $admin->roles()->attach($role->id);

        return $admin;
    }

    public function test_customer_dossier_shows_building_memberships_without_exposing_finance_to_limited_admin(): void
    {
        $customer = User::create([
            'mobile' => '09120000881',
            'first_name' => 'مشتری',
            'last_name' => 'آزمایشی',
            'status' => 'active',
        ]);
        $building = Building::create([
            'name' => 'ساختمان پرونده',
            'code' => 'D881',
            'created_by' => $customer->id,
        ]);
        $role = Role::create(['key' => 'owner', 'name_fa' => 'مالک']);
        BuildingUserRole::create([
            'building_id' => $building->id,
            'user_id' => $customer->id,
            'role_id' => $role->id,
            'status' => 'active',
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'پلن محرمانه تست',
            'units_limit' => 20,
            'duration_months' => 1,
            'price' => 500000,
            'is_active' => true,
        ]);
        Subscription::create([
            'user_id' => $customer->id,
            'building_id' => $building->id,
            'plan_id' => $plan->id,
            'units_limit' => 20,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => 'active',
        ]);
        SubscriptionPayment::create([
            'user_id' => $customer->id,
            'amount' => 500000,
            'gateway' => 'test',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $limited = $this->admin(['users.view']);
        $this->actingAs($limited, 'admin')
            ->get(route('admin.building-users.show', $customer->id))
            ->assertOk()
            ->assertSee('پرونده مشتری')
            ->assertSee('ساختمان پرونده')
            ->assertSee('مالک')
            ->assertDontSee('پلن محرمانه تست')
            ->assertDontSee('۵۰۰٬۰۰۰');
    }

    public function test_customer_dossier_shows_subscription_and_payment_history_to_finance_authorized_admin(): void
    {
        $customer = User::create([
            'mobile' => '09120000882',
            'first_name' => 'مشتری',
            'last_name' => 'مالی',
            'status' => 'active',
        ]);
        $building = Building::create([
            'name' => 'ساختمان مالی',
            'code' => 'D882',
            'created_by' => $customer->id,
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'پلن مالی تست',
            'units_limit' => 12,
            'duration_months' => 1,
            'price' => 250000,
            'is_active' => true,
        ]);
        Subscription::create([
            'user_id' => $customer->id,
            'building_id' => $building->id,
            'plan_id' => $plan->id,
            'units_limit' => 12,
            'starts_at' => now(),
            'expires_at' => now()->addMonth(),
            'status' => 'active',
        ]);
        SubscriptionPayment::create([
            'user_id' => $customer->id,
            'amount' => 250000,
            'gateway' => 'test',
            'ref_id' => 'DOSSIER-REF',
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $admin = $this->admin(['users.view', 'subscriptions.manage']);
        $this->actingAs($admin, 'admin')
            ->get(route('admin.building-users.show', $customer->id))
            ->assertOk()
            ->assertSee('پلن مالی تست')
            ->assertSee('پرداخت‌های اشتراک')
            ->assertSee('DOSSIER-REF');
    }

    public function test_customer_dossier_requires_user_directory_permission(): void
    {
        $customer = User::create(['mobile' => '09120000883', 'first_name' => 'بدون', 'last_name' => 'مجوز']);
        $admin = $this->admin(['plans.manage']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.building-users.show', $customer->id))
            ->assertForbidden();
    }
}
