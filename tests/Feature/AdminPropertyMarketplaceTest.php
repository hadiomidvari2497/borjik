<?php

namespace Tests\\Feature;

use App\\Models\\AdminRole;
use App\\Models\\AdminUser;
use App\\Models\\PropertyListing;
use App\\Models\\PropertyListingCreditTransaction;
use App\\Models\\PropertyListingPackageOrder;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class AdminPropertyMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $permissions = [], bool $super = false): AdminUser
    {
        static $sequence = 0;
        $sequence++;
        $admin = AdminUser::create([
            'username' => 'marketplace_admin_'.$sequence,
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => $super,
        ]);

        if ($permissions !== []) {
            $role = AdminRole::create(['name' => 'marketplace_role_'.$sequence]);
            $role->syncPermissions($permissions);
            $admin->roles()->attach($role->id);
        }

        return $admin;
    }

    private function user(): User
    {
        return User::create([
            'mobile' => '0912'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'first_name' => 'کاربر',
            'last_name' => 'آگهی',
            'status' => 'active',
        ]);
    }

    public function test_marketplace_admin_routes_require_the_dedicated_permission(): void
    {
        $admin = $this->admin(['plans.manage']);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.property-listings.index'))
            ->assertForbidden();
    }

    public function test_marketplace_dashboard_shows_listing_package_revenue_and_credit_metrics(): void
    {
        $admin = $this->admin(super: true);
        $user = $this->user();

        PropertyListing::create([
            'user_id' => $user->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آگهی مدیریتی آزمایشی',
            'description' => 'توضیحات آگهی',
            'city' => 'تهران',
            'status' => 'active',
        ]);

        $order = PropertyListingPackageOrder::create([
            'user_id' => $user->id,
            'quantity' => 3,
            'amount' => 120000,
            'gateway' => 'zarinpal',
            'gateway_id' => 'legacy',
            'status' => 'paid',
            'paid_at' => now(),
            'ref_id' => 'MARKET-REF-1',
            'raw_response' => ['gateway_name' => 'زرین‌پال'],
        ]);

        PropertyListingCreditTransaction::create([
            'user_id' => $user->id,
            'package_order_id' => $order->id,
            'quantity' => 3,
            'type' => 'purchase',
            'description' => 'خرید بسته',
        ]);
        PropertyListingCreditTransaction::create([
            'user_id' => $user->id,
            'quantity' => -1,
            'type' => 'consume',
            'description' => 'مصرف اعتبار',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.property-listings.index'))
            ->assertOk()
            ->assertSee('مدیریت بازارچه املاک')
            ->assertSee('آگهی مدیریتی آزمایشی')
            ->assertSee('۱۲۰٬۰۰۰ تومان')
            ->assertSee('اعتبار خریداری‌شده')
            ->assertSee('اعتبار مصرف‌شده')
            ->assertSee('زرین‌پال');
    }

    public function test_admin_can_review_and_change_listing_status_with_audit_note(): void
    {
        $admin = $this->admin(super: true);
        $user = $this->user();
        $listing = PropertyListing::create([
            'user_id' => $user->id,
            'listing_type' => 'rent',
            'property_type' => 'residential',
            'title' => 'آگهی نیازمند بررسی',
            'description' => 'توضیحات',
            'city' => 'تهران',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.property-listings.show', $listing->id))
            ->assertOk()
            ->assertSee('رسیدگی و وضعیت انتشار')
            ->assertSee('آگهی نیازمند بررسی');

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.property-listings.status', $listing->id), [
                'status' => 'rejected',
                'moderation_note' => 'اطلاعات آگهی نیازمند اصلاح است.',
            ])
            ->assertRedirect(route('admin.property-listings.show', $listing->id));

        $this->assertDatabaseHas('property_listings', [
            'id' => $listing->id,
            'status' => 'rejected',
            'moderation_note' => 'اطلاعات آگهی نیازمند اصلاح است.',
            'moderated_by' => $admin->id,
        ]);
        $this->assertNotNull($listing->fresh()->moderated_at);
    }
}
