<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\PhonebookEntry;
use App\Models\Role;
use App\Models\User;
use App\Support\CurrentBuilding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhonebookTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_manage_building_contacts_and_see_active_building_members(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $manager = $this->user('09120000101');
        $resident = $this->user('09120000102');
        $building = Building::create(['name' => 'ساختمان دفترچه', 'code' => 'PHB001', 'created_by' => $manager->id]);
        $managerMembership = $this->membership($manager, $building, 'manager');
        $this->membership($resident, $building, 'tenant');

        $this->actingAs($manager)->withSession($this->sessionFor($building, $managerMembership))
            ->post(route('phonebook.store'), [
                'scope' => 'building',
                'name' => 'آتش‌نشانی',
                'phone' => '۱۲۵',
                'category' => 'fire',
            ])->assertRedirect(route('phonebook.index', ['tab' => 'building']));

        $this->actingAs($manager)->withSession($this->sessionFor($building, $managerMembership))
            ->get(route('phonebook.index', ['tab' => 'building']))
            ->assertOk()
            ->assertSee('آتش‌نشانی')
            ->assertSee('کاربر آزمایشی');
    }

    public function test_resident_can_manage_private_contacts_but_cannot_see_building_contacts(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $manager = $this->user('09120000103');
        $resident = $this->user('09120000104');
        $building = Building::create(['name' => 'ساختمان خصوصی', 'code' => 'PHB002', 'created_by' => $manager->id]);
        $managerMembership = $this->membership($manager, $building, 'manager');
        $residentMembership = $this->membership($resident, $building, 'tenant');

        app(CurrentBuilding::class)->actingAs($building->id, fn () => PhonebookEntry::create([
            'user_id' => null, 'name' => 'شمارهٔ محرمانهٔ ساختمان', 'phone' => '۱۲۳۴۵', 'category' => 'other',
        ]));

        $this->actingAs($resident)->withSession($this->sessionFor($building, $residentMembership))
            ->post(route('phonebook.store'), [
                'scope' => 'personal',
                'name' => 'مخاطب شخصی',
                'phone' => '۰۹۱۲۱۲۳۴۵۶۷',
                'category' => 'other',
            ])->assertRedirect(route('phonebook.index', ['tab' => 'personal']));

        $this->actingAs($resident)->withSession($this->sessionFor($building, $residentMembership))
            ->get(route('phonebook.index'))
            ->assertOk()
            ->assertSee('مخاطب شخصی')
            ->assertDontSee('شمارهٔ محرمانهٔ ساختمان');

        $this->actingAs($resident)->withSession($this->sessionFor($building, $residentMembership))
            ->get(route('phonebook.index', ['tab' => 'building']))
            ->assertOk()
            ->assertDontSee('شمارهٔ محرمانهٔ ساختمان');
    }

    public function test_user_cannot_update_another_users_personal_contact(): void
    {
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $first = $this->user('09120000105');
        $second = $this->user('09120000106');
        $building = Building::create(['name' => 'ساختمان حریم خصوصی', 'code' => 'PHB003', 'created_by' => $first->id]);
        $firstMembership = $this->membership($first, $building, 'tenant');
        $secondMembership = $this->membership($second, $building, 'tenant');
        $contact = app(CurrentBuilding::class)->actingAs($building->id, fn () => PhonebookEntry::create([
            'user_id' => $first->id, 'name' => 'مخاطب اول', 'phone' => '09121112222', 'category' => 'other',
        ]));

        $this->actingAs($second)->withSession($this->sessionFor($building, $secondMembership))
            ->put(route('phonebook.update', $contact->id), [
                'name' => 'تغییر غیرمجاز',
                'phone' => '09123334444',
                'category' => 'other',
            ])->assertForbidden();

        $this->assertSame('مخاطب اول', app(CurrentBuilding::class)->actingAs($building->id, fn () => PhonebookEntry::findOrFail($contact->id)->name));
    }

    private function user(string $mobile): User
    {
        return User::create([
            'mobile' => $mobile,
            'first_name' => 'کاربر',
            'last_name' => 'آزمایشی',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
    }

    private function membership(User $user, Building $building, string $role): BuildingUserRole
    {
        return BuildingUserRole::create([
            'building_id' => $building->id,
            'user_id' => $user->id,
            'role_id' => Role::where('key', $role)->value('id'),
            'status' => 'active',
        ]);
    }

    private function sessionFor(Building $building, BuildingUserRole $membership): array
    {
        return [
            'current_building_id' => $building->id,
            'current_building_user_role_id' => $membership->id,
        ];
    }
}
