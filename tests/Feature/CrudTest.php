<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\ChargeSetting;
use App\Models\ExpenseCategory;
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

/**
 * فرض (README): routes/auth.php و routes/app.php در web.php لود شده‌اند.
 */
class CrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function owner(string $mobile = '09120000001'): User
    {
        return User::create(['mobile' => $mobile, 'first_name' => 'مالک', 'last_name' => 'تست', 'status' => 'active', 'profile_completed_at' => now()]);
    }

    private function subscribe(User $user, int $limit = 5, int $days = 30): Subscription
    {
        $plan = SubscriptionPlan::create(['name' => "plan-{$limit}-{$days}", 'units_limit' => $limit, 'duration_months' => 1, 'price' => 0]);

        return Subscription::create([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'units_limit' => $limit,
            'starts_at' => now()->subDay(), 'expires_at' => now()->addDays($days), 'status' => 'active',
        ]);
    }

    /** ساختمان آماده با مالک و اشتراک. @return array{0: User, 1: Building, 2: Block} */
    private function building(int $limit = 5, int $days = 30, string $mobile = '09120000001'): array
    {
        $owner = $this->owner($mobile);
        $sub = $this->subscribe($owner, $limit, $days);
        $b = Building::create(['name' => 'برج '.$mobile, 'code' => substr($mobile, -5), 'created_by' => $owner->id, 'subscription_id' => $sub->id]);
        BuildingUserRole::create(['building_id' => $b->id, 'user_id' => $owner->id, 'role_id' => Role::where('key', 'owner')->value('id'), 'status' => 'active']);
        $block = app(CurrentBuilding::class)->actingAs($b->id, fn () => Block::create(['number' => 1, 'name' => 'الف', 'floors_count' => 5]));

        return [$owner, $b, $block];
    }

    private function as(User $u, Building $b)
    {
        return $this->actingAs($u)->withSession(['current_building_id' => $b->id]);
    }

    public function test_building_creation_requires_a_subscription(): void
    {
        $user = $this->owner();

        $this->actingAs($user)->post(route('buildings.store'), ['name' => 'برج تست', 'type' => 'residential'])
            ->assertSessionHasErrors('general');

        $this->assertSame(0, Building::count());
    }

    public function test_creating_a_building_makes_the_creator_owner_and_seeds_defaults(): void
    {
        $user = $this->owner();
        $sub = $this->subscribe($user);

        $this->actingAs($user)->post(route('buildings.store'), ['name' => 'برج تست', 'type' => 'residential', 'postal_code' => '۱۲۳۴۵۶۷۸۹۰', 'built_at' => '۱۳۹۵/۰۶/۱۵'])
            ->assertRedirect(route('dashboard'));

        $b = Building::where('name', 'برج تست')->firstOrFail();
        $this->assertSame($sub->id, $b->subscription_id);
        $this->assertSame(5, strlen($b->code));
        $this->assertSame('1234567890', $b->postal_code);
        $this->assertSame(1, BuildingUserRole::where(['building_id' => $b->id, 'user_id' => $user->id, 'status' => 'active'])->count());

        $cb = app(CurrentBuilding::class);
        $this->assertSame(1, $cb->actingAs($b->id, fn () => ChargeSetting::count()));
        $this->assertSame(6, $cb->actingAs($b->id, fn () => ExpenseCategory::count()));
    }

    public function test_bulk_units_respect_the_subscription_limit_atomically(): void
    {
        [$owner, $b, $block] = $this->building(limit: 5);
        $payload = ['block_id' => $block->id, 'numbering_mode' => 'sequential', 'count' => 6, 'start_number' => 1, 'units_per_floor' => 2, 'start_floor' => 1, 'area_m2' => 80];

        $this->as($owner, $b)->post(route('units.bulk.store'), $payload)->assertSessionHasErrors('general');
        $this->assertSame(0, app(CurrentBuilding::class)->actingAs($b->id, fn () => Unit::count()));

        $this->as($owner, $b)->post(route('units.bulk.store'), ['count' => 5] + $payload)->assertRedirect(route('units.index'));
        $units = app(CurrentBuilding::class)->actingAs($b->id, fn () => Unit::orderBy('number')->get());
        $this->assertCount(5, $units);
        $this->assertSame([1, 1, 2, 2, 3], $units->pluck('floor')->all());
    }

    public function test_bulk_units_can_use_block_floor_unit_numbering(): void
    {
        [$owner, $building, $block] = $this->building(limit: 20);
        $this->as($owner, $building)->post(route('units.bulk.store'), [
            'block_id' => $block->id,
            'numbering_mode' => 'structured',
            'count' => 4,
            'units_per_floor' => 4,
            'start_floor' => 2,
            'area_m2' => 80,
        ])->assertRedirect(route('units.index'));

        $units = app(CurrentBuilding::class)->actingAs($building->id, fn () => Unit::orderBy('number')->get());
        $this->assertSame(['121', '122', '123', '124'], $units->pluck('number')->all());
        $this->assertSame([2, 2, 2, 2], $units->pluck('floor')->all());
    }

    public function test_bulk_structured_numbers_support_two_digit_block_floor_and_unit_parts(): void
    {
        [$owner, $building] = $this->building(limit: 20);
        $block = app(CurrentBuilding::class)->actingAs($building->id, fn () => Block::create([
            'number' => 12,
            'name' => 'بلوک دوازده',
            'floors_count' => 12,
        ]));

        $this->as($owner, $building)->post(route('units.bulk.store'), [
            'block_id' => $block->id,
            'numbering_mode' => 'structured',
            'count' => 10,
            'units_per_floor' => 10,
            'start_floor' => 10,
            'area_m2' => 80,
        ])->assertRedirect(route('units.index'));

        $units = app(CurrentBuilding::class)->actingAs($building->id, fn () => Unit::where('block_id', $block->id)->orderBy('number')->get());
        $this->assertSame('121001', $units->first()->number);
        $this->assertSame('121010', $units->last()->number);
        $this->assertSame([10], $units->pluck('floor')->unique()->values()->all());
    }

    public function test_expired_subscription_locks_all_building_features_and_keeps_renewal_access(): void
    {
        [$owner, $b] = $this->building(days: -1);

        $this->as($owner, $b)->get(route('units.index'))
            ->assertRedirect(route('subscription.show'))
            ->assertSessionHas('warning');
        $this->as($owner, $b)->get(route('units.create'))
            ->assertRedirect(route('subscription.show'));
        $this->as($owner, $b)->get(route('subscription.show'))->assertOk();
    }

    public function test_block_with_units_cannot_be_deleted(): void
    {
        [$owner, $b, $block] = $this->building();
        app(CurrentBuilding::class)->actingAs($b->id, fn () => Unit::create(['block_id' => $block->id, 'number' => '1', 'area_m2' => 90]));

        $this->as($owner, $b)->delete(route('blocks.destroy', $block))->assertSessionHasErrors('general');
        $this->assertSame(1, app(CurrentBuilding::class)->actingAs($b->id, fn () => Block::count()));

        app(CurrentBuilding::class)->actingAs($b->id, fn () => Unit::query()->forceDelete());
        $this->as($owner, $b)->delete(route('blocks.destroy', $block))->assertRedirect(route('blocks.index'));
        $this->assertSame(0, app(CurrentBuilding::class)->actingAs($b->id, fn () => Block::count()));
    }

    public function test_unit_with_history_cannot_be_deleted_but_a_clean_one_can(): void
    {
        [$owner, $b, $block] = $this->building();
        $cb = app(CurrentBuilding::class);
        $withHistory = $cb->actingAs($b->id, fn () => Unit::create(['block_id' => $block->id, 'number' => '1', 'area_m2' => 90]));
        $clean = $cb->actingAs($b->id, fn () => Unit::create(['block_id' => $block->id, 'number' => '2', 'area_m2' => 90]));
        $cb->actingAs($b->id, fn () => UnitParty::create(['unit_id' => $withHistory->id, 'user_id' => $owner->id, 'relation' => 'owner', 'from_date' => today()]));

        $this->as($owner, $b)->delete(route('units.destroy', $withHistory))->assertSessionHasErrors('general');
        $this->as($owner, $b)->delete(route('units.destroy', $clean))->assertRedirect(route('units.index'));

        $this->assertDatabaseHas('units', ['id' => $withHistory->id]);
        $this->assertDatabaseMissing('units', ['id' => $clean->id]);
    }

    public function test_a_unit_of_another_building_is_not_reachable(): void
    {
        [$owner1, $b1] = $this->building(mobile: '09120000001');
        [, $b2, $block2] = $this->building(mobile: '09120000002');
        $foreign = app(CurrentBuilding::class)->actingAs($b2->id, fn () => Unit::create(['block_id' => $block2->id, 'number' => '9', 'area_m2' => 70]));

        $this->as($owner1, $b1)->get(route('units.edit', $foreign))->assertNotFound();
        $this->as($owner1, $b1)->delete(route('units.destroy', $foreign))->assertNotFound();
        $this->assertDatabaseHas('units', ['id' => $foreign->id]);
    }

    public function test_unit_number_must_be_unique_inside_a_block(): void
    {
        [$owner, $b, $block] = $this->building();
        $data = ['block_id' => $block->id, 'number' => '1', 'floor' => 1, 'area_m2' => 80];

        $this->as($owner, $b)->post(route('units.store'), $data)->assertRedirect(route('units.index'));
        $this->as($owner, $b)->post(route('units.store'), $data)->assertSessionHasErrors('number');
    }

    public function test_new_units_have_no_residents_and_the_count_is_only_editable_for_occupied_units(): void
    {
        [$owner, $b, $block] = $this->building();
        $this->as($owner, $b)->post(route('units.store'), ['block_id' => $block->id, 'number' => '1', 'floor' => 0, 'area_m2' => 80, 'residents_count' => 5]);

        $unit = app(CurrentBuilding::class)->actingAs($b->id, fn () => Unit::firstOrFail());
        $this->assertSame(0, $unit->residents_count); // ورودی نادیده گرفته می‌شود

        $this->as($owner, $b)->get(route('units.edit', $unit))->assertOk()->assertDontSee('تعداد ساکنین فعلی');

        app(CurrentBuilding::class)->actingAs($b->id, fn () => $unit->update(['status' => 'sold']));
        $this->as($owner, $b)->put(route('units.update', $unit), ['block_id' => $block->id, 'number' => '1', 'floor' => 0, 'area_m2' => 80, 'residents_count' => 3]);
        $this->assertSame(3, $unit->fresh()->residents_count);
    }
}
