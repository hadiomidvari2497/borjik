<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Unit;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** پارکینگ و انباری. فرض‌ها مثل CrudTest: مسیرها لود شده و Middleware «building» قبل از SubstituteBindings است. */
class AmenityTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Building $building;
    private Block $block;
    private Unit $u1;
    private Unit $u2;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = User::create(['mobile' => '09120000001', 'first_name' => 'م', 'last_name' => 'ت', 'status' => 'active', 'profile_completed_at' => now()]);
        $plan = SubscriptionPlan::create(['name' => 'p', 'units_limit' => 20, 'duration_months' => 1, 'price' => 0]);
        $sub = Subscription::create(['user_id' => $this->owner->id, 'plan_id' => $plan->id, 'units_limit' => 20, 'starts_at' => now()->subDay(), 'expires_at' => now()->addMonth(), 'status' => 'active']);
        $this->building = Building::create(['name' => 'برج', 'code' => '11111', 'created_by' => $this->owner->id, 'subscription_id' => $sub->id]);
        BuildingUserRole::create(['building_id' => $this->building->id, 'user_id' => $this->owner->id, 'role_id' => Role::where('key', 'owner')->value('id'), 'status' => 'active']);

        app(CurrentBuilding::class)->actingAs($this->building->id, function () {
            $this->block = Block::create(['number' => 1, 'name' => 'الف', 'floors_count' => 5]);
            $this->u1 = Unit::create(['block_id' => $this->block->id, 'number' => '1', 'area_m2' => 80]);
            $this->u2 = Unit::create(['block_id' => $this->block->id, 'number' => '2', 'area_m2' => 80]);
        });
    }

    private function as()
    {
        return $this->actingAs($this->owner)->withSession(['current_building_id' => $this->building->id]);
    }

    private function db(callable $fn)
    {
        return app(CurrentBuilding::class)->actingAs($this->building->id, $fn);
    }

    /** چرخه‌ی کامل برای هر دو نوع: ثبت ← اختصاص ← اختصاص دوم رد می‌شود ← آزادسازی ← اختصاص به واحد دیگر ← حذف. */
    private function lifecycle(string $prefix, string $table, string $assignTable, string $fk, string $open): void
    {
        $this->as()->post(route("{$prefix}.store"), ['number' => '۱۲', 'block_id' => $this->block->id, 'floor' => -1])->assertRedirect(route("{$prefix}.index"));
        $this->assertDatabaseHas($table, ['number' => '12', 'status' => 'free', 'floor' => -1]);
        $id = (int) \DB::table($table)->value('id');

        $this->as()->post(route("{$prefix}.assign", $id), ['unit_id' => $this->u1->id])->assertSessionHasNoErrors();
        $this->assertDatabaseHas($table, ['id' => $id, 'status' => 'assigned']);
        $this->assertDatabaseHas($assignTable, [$fk => $id, 'unit_id' => $this->u1->id, $open => $id]);

        $this->as()->post(route("{$prefix}.assign", $id), ['unit_id' => $this->u2->id])->assertSessionHasErrors('general');
        $this->assertSame(1, \DB::table($assignTable)->count());

        $this->as()->delete(route("{$prefix}.destroy", $id))->assertSessionHasErrors('general'); // بدون حذف چون در حال اختصاص است

        $this->as()->post(route("{$prefix}.release", $id))->assertSessionHasNoErrors();
        $this->assertDatabaseHas($table, ['id' => $id, 'status' => 'free']);
        $row = \DB::table($assignTable)->first();
        $this->assertNotNull($row->released_at);
        $this->assertNull($row->{$open});

        $this->as()->post(route("{$prefix}.assign", $id), ['unit_id' => $this->u2->id])->assertSessionHasNoErrors();
        $this->assertSame(2, \DB::table($assignTable)->count()); // تاریخچه حفظ می‌شود

        $this->as()->post(route("{$prefix}.release", $id));
        $this->as()->delete(route("{$prefix}.destroy", $id))->assertSessionHasErrors('general'); // سابقه دارد

        $this->as()->post(route("{$prefix}.store"), ['number' => '13']);
        $clean = (int) \DB::table($table)->where('number', '13')->value('id');
        $this->as()->delete(route("{$prefix}.destroy", $clean))->assertRedirect(route("{$prefix}.index"));
        $this->assertDatabaseMissing($table, ['id' => $clean]);
    }

    public function test_parking_lifecycle(): void
    {
        $this->lifecycle('parkings', 'parkings', 'parking_assignments', 'parking_id', 'open_parking_id');
    }

    public function test_storage_lifecycle(): void
    {
        $this->lifecycle('storages', 'storages', 'storage_assignments', 'storage_id', 'open_storage_id');
    }

    public function test_one_unit_can_hold_several_parkings(): void
    {
        $this->as()->post(route('parkings.bulk.store'), ['count' => 2, 'start_number' => 1, 'block_id' => $this->block->id]);
        foreach (\DB::table('parkings')->pluck('id') as $id) {
            $this->as()->post(route('parkings.assign', $id), ['unit_id' => $this->u1->id])->assertSessionHasNoErrors();
        }

        $this->assertSame(2, \DB::table('parking_assignments')->where('unit_id', $this->u1->id)->whereNull('released_at')->count());
        $this->as()->get(route('units.index'))->assertOk();
    }

    public function test_duplicate_numbers_are_rejected_even_without_a_block(): void
    {
        $this->as()->post(route('storages.store'), ['number' => '5'])->assertSessionHasNoErrors();
        $this->as()->post(route('storages.store'), ['number' => '5'])->assertSessionHasErrors('number');
        $this->as()->post(route('storages.bulk.store'), ['count' => 3, 'start_number' => 4])->assertSessionHasErrors('general');
        $this->assertSame(1, \DB::table('storages')->count());
    }

    public function test_a_unit_with_parking_history_cannot_be_deleted(): void
    {
        $this->as()->post(route('parkings.store'), ['number' => '1']);
        $this->as()->post(route('parkings.assign', \DB::table('parkings')->value('id')), ['unit_id' => $this->u1->id]);

        $this->as()->delete(route('units.destroy', $this->u1))->assertSessionHasErrors('general');
        $this->assertDatabaseHas('units', ['id' => $this->u1->id]);
    }
}
