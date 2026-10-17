<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Invoice;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\UnitParty;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyUnitPageTest extends TestCase
{
    use RefreshDatabase;

    private User $tenant;
    private User $staff;
    private Building $building;
    private Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->tenant = $this->user('09120000021', 'ساکن');
        $this->staff = $this->user('09120000022', 'پرسنل');

        $plan = SubscriptionPlan::create([
            'name' => 'test',
            'units_limit' => 20,
            'duration_months' => 1,
            'price' => 0,
        ]);
        $subscription = Subscription::create([
            'user_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'units_limit' => 20,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addMonth(),
            'status' => 'active',
        ]);
        $this->building = Building::create([
            'name' => 'برج آزمایشی',
            'code' => '81234',
            'created_by' => $this->tenant->id,
            'subscription_id' => $subscription->id,
        ]);

        $this->join($this->tenant, 'tenant');
        $this->join($this->staff, 'cleaning');

        $this->db(function () {
            $block = Block::create(['number' => 1, 'name' => 'بلوک آفتاب', 'floors_count' => 6]);
            $this->unit = Unit::create([
                'block_id' => $block->id,
                'number' => '12',
                'floor' => 3,
                'area_m2' => 86,
                'bedrooms' => 2,
                'direction' => 'north',
                'status' => 'rented',
                'residents_count' => 1,
            ]);
            Unit::create([
                'block_id' => $block->id,
                'number' => '99',
                'floor' => 9,
                'area_m2' => 140,
                'status' => 'vacant',
                'residents_count' => 0,
            ]);
            UnitParty::create([
                'unit_id' => $this->unit->id,
                'user_id' => $this->tenant->id,
                'relation' => 'tenant',
                'from_date' => today(),
            ]);
            Invoice::create([
                'unit_id' => $this->unit->id,
                'payer_user_id' => $this->tenant->id,
                'type' => 'charge',
                'number' => 'MYU-1',
                'amount' => 250000,
                'paid_amount' => 50000,
                'issued_at' => today(),
                'due_date' => today()->addDays(7),
                'status' => 'partial',
            ]);
        });
    }

    private function user(string $mobile, string $name): User
    {
        return User::create([
            'mobile' => $mobile,
            'first_name' => $name,
            'last_name' => 'آزمایشی',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
    }

    private function join(User $user, string $role): void
    {
        BuildingUserRole::create([
            'building_id' => $this->building->id,
            'user_id' => $user->id,
            'role_id' => Role::where('key', $role)->value('id'),
            'status' => 'active',
        ]);
    }

    private function db(callable $callback)
    {
        return app(CurrentBuilding::class)->actingAs($this->building->id, $callback);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['current_building_id' => $this->building->id]);
    }

    public function test_resident_sees_their_unit_summary_and_own_financial_balance(): void
    {
        $this->as($this->tenant)
            ->get(route('my.units'))
            ->assertOk()
            ->assertSee('فضای اختصاصی من')
            ->assertSee('بلوک آفتاب')
            ->assertSee('واحد')
            ->assertSee('۲۰۰٬۰۰۰')
            ->assertDontSee('واحد ۹۹');
    }

    public function test_user_without_own_unit_permission_cannot_open_the_page(): void
    {
        $this->as($this->staff)
            ->get(route('my.units'))
            ->assertForbidden();
    }
}
