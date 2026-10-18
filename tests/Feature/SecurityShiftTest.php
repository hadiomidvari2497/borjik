<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\SecurityShift;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityShiftTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private User $owner;
    private User $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner = $this->user('09120000021', 'مالک', 'owner');
        $this->building = Building::create(['name' => 'ساختمان شیفت', 'code' => '66121', 'created_by' => $this->owner->id]);
        $this->join($this->owner, 'owner');
        $this->guard = $this->user('09120000022', 'نگهبان', 'security');
        $this->join($this->guard, 'security');
    }

    private function user(string $mobile, string $name, string $role): User
    {
        return User::create(['mobile' => $mobile, 'first_name' => $name, 'last_name' => $role, 'status' => 'active', 'profile_completed_at' => now()]);
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

    private function open(User $user, string $method, string $uri, array $data = [])
    {
        return $this->actingAs($user)->withSession(['current_building_id' => $this->building->id])->{$method}($uri, $data);
    }

    public function test_manager_can_create_weekly_guard_shift(): void
    {
        $this->open($this->owner, 'post', route('shifts.store'), [
            'user_id' => $this->guard->id,
            'schedule_type' => 'weekly',
            'weekday' => 6,
            'start_time' => '20:00',
            'end_time' => '08:00',
            'location' => 'ورودی اصلی',
        ])->assertRedirect(route('shifts.index'));

        $this->assertDatabaseHas('security_shifts', [
            'building_id' => $this->building->id,
            'user_id' => $this->guard->id,
            'weekday' => 6,
            'shift_date' => null,
            'location' => 'ورودی اصلی',
        ]);
    }

    public function test_manager_can_create_jalali_dated_shift(): void
    {
        $this->open($this->owner, 'post', route('shifts.store'), [
            'user_id' => $this->guard->id,
            'schedule_type' => 'date',
            'shift_date' => '1405/07/26',
            'start_time' => '08:00',
            'end_time' => '16:00',
        ])->assertRedirect(route('shifts.index'));

        $this->assertDatabaseHas('security_shifts', [
            'building_id' => $this->building->id,
            'user_id' => $this->guard->id,
            'shift_date' => '2026-10-18 00:00:00',
        ]);
    }

    public function test_guard_can_only_see_own_shifts_and_cannot_manage_them(): void
    {
        $this->open($this->owner, 'post', route('shifts.store'), [
            'user_id' => $this->guard->id,
            'schedule_type' => 'weekly',
            'weekday' => 1,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ])->assertRedirect(route('shifts.index'));

        $this->open($this->guard, 'get', route('shifts.index'))->assertOk()->assertSee('نگهبان')->assertDontSee('حذف');
        $this->open($this->guard, 'post', route('shifts.store'), [
            'user_id' => $this->guard->id,
            'schedule_type' => 'weekly',
            'weekday' => 1,
            'start_time' => '08:00',
            'end_time' => '16:00',
        ])->assertForbidden();
        $this->assertDatabaseCount('security_shifts', 1);
    }
}
