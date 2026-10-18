<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\Supply;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyInventoryTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::create(['mobile' => '09120000031', 'first_name' => 'مالک', 'last_name' => 'آزمایش', 'status' => 'active', 'profile_completed_at' => now()]);
        $this->building = Building::create(['name' => 'ساختمان ملزومات', 'code' => '77131', 'created_by' => $this->owner->id]);
        BuildingUserRole::create([
            'building_id' => $this->building->id,
            'user_id' => $this->owner->id,
            'role_id' => Role::where('key', 'owner')->value('id'),
            'status' => 'active',
        ]);
    }

    private function open(string $method, string $uri, array $data = [])
    {
        return $this->actingAs($this->owner)->withSession(['current_building_id' => $this->building->id])->{$method}($uri, $data);
    }

    private function supplyInBuilding(string $name): Supply
    {
        return app(CurrentBuilding::class)->actingAs($this->building->id, fn () => Supply::where('name', $name)->firstOrFail());
    }

    public function test_creating_supply_records_initial_stock_movement(): void
    {
        $this->open('post', route('supplies.store'), [
            'name' => 'لامپ',
            'unit' => 'عدد',
            'min_quantity' => '3',
            'initial_quantity' => '10',
            'reason' => 'خرید اولیه',
        ])->assertRedirect(route('supplies.index'));

        $supply = $this->supplyInBuilding('لامپ');
        $this->assertSame('10.00', $supply->quantity);
        $this->assertDatabaseHas('supply_movements', ['supply_id' => $supply->id, 'delta' => '10.00', 'reason' => 'خرید اولیه', 'building_id' => $this->building->id]);
    }

    public function test_consumption_cannot_make_stock_negative(): void
    {
        $this->open('post', route('supplies.store'), [
            'name' => 'کیسه زباله',
            'unit' => 'بسته',
            'min_quantity' => '1',
            'initial_quantity' => '2',
        ])->assertRedirect(route('supplies.index'));
        $supply = $this->supplyInBuilding('کیسه زباله');

        $this->open('post', route('supplies.move', $supply), [
            'direction' => 'out', 'quantity' => '3', 'reason' => 'مصرف تست',
        ])->assertRedirect(route('supplies.index'));

        $this->assertSame('2.00', $this->supplyInBuilding('کیسه زباله')->quantity);
        $this->assertDatabaseCount('supply_movements', 1);
    }

    public function test_supply_inventory_is_scoped_to_the_current_building(): void
    {
        $this->open('post', route('supplies.store'), [
            'name' => 'فقط ساختمان اول', 'min_quantity' => '0', 'initial_quantity' => '1',
        ])->assertRedirect(route('supplies.index'));

        $other = Building::create(['name' => 'ساختمان دوم', 'code' => '77132', 'created_by' => $this->owner->id]);
        $otherSupplyId = app(\App\Support\CurrentBuilding::class)->actingAs($other->id, function () {
            return Supply::create(['name' => 'فقط ساختمان دوم', 'unit' => 'عدد', 'quantity' => 5, 'min_quantity' => 0])->id;
        });

        $this->open('get', route('supplies.index'))->assertOk()->assertSee('فقط ساختمان اول')->assertDontSee('فقط ساختمان دوم');
        $this->assertNotNull($otherSupplyId);
    }
}
