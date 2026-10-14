<?php

namespace Tests\Feature;

use App\Exceptions\TenantException;
use App\Models\Block;
use App\Models\Building;
use App\Models\User;
use App\Support\CurrentBuilding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** تست نشت داده بین ساختمان‌ها (بند ۲.۱ سند). */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Building $b1;
    private Building $b2;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::create(['mobile' => '09120000000', 'first_name' => 'تست', 'last_name' => 'کاربر']);
        $this->b1 = Building::create(['name' => 'برج یک', 'code' => '11111', 'created_by' => $user->id]);
        $this->b2 = Building::create(['name' => 'برج دو', 'code' => '22222', 'created_by' => $user->id]);

        $cb = app(CurrentBuilding::class);
        $cb->actingAs($this->b1->id, fn () => Block::create(['number' => 1, 'name' => 'الف', 'floors_count' => 5]));
        $cb->actingAs($this->b2->id, fn () => Block::create(['number' => 1, 'name' => 'ب', 'floors_count' => 3]));
    }

    public function test_each_building_only_sees_its_own_rows(): void
    {
        $cb = app(CurrentBuilding::class);

        $this->assertSame(['الف'], $cb->actingAs($this->b1->id, fn () => Block::pluck('name')->all()));
        $this->assertSame(['ب'], $cb->actingAs($this->b2->id, fn () => Block::pluck('name')->all()));
    }

    public function test_queries_fail_closed_without_a_current_building(): void
    {
        $this->assertSame(0, Block::count());
    }

    public function test_writing_into_another_building_is_rejected(): void
    {
        $this->expectException(TenantException::class);

        app(CurrentBuilding::class)->actingAs($this->b1->id, fn () => Block::create([
            'building_id' => $this->b2->id, 'number' => 2, 'name' => 'نشت', 'floors_count' => 1,
        ]));
    }

    public function test_updating_a_record_cannot_move_it_to_another_building(): void
    {
        $cb = app(CurrentBuilding::class);

        $this->expectException(TenantException::class);

        $cb->actingAs($this->b1->id, function () {
            $block = Block::firstOrFail();
            $block->building_id = $this->b2->id;
            $block->save();
        });
    }

    public function test_bypass_sees_all_buildings(): void
    {
        $this->assertSame(2, app(CurrentBuilding::class)->withoutScope(fn () => Block::count()));
    }
}
