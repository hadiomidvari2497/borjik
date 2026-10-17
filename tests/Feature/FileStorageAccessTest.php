<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\File;
use App\Models\Role;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileStorageAccessTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private User $manager;
    private User $maintenance;
    private BuildingUserRole $managerMembership;
    private BuildingUserRole $maintenanceMembership;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);

        $this->manager = $this->makeUser('09123330001', 'مدیر');
        $this->building = Building::create([
            'name' => 'برج فایل‌ها',
            'code' => 'FIL001',
            'created_by' => $this->manager->id,
        ]);
        $this->managerMembership = $this->membership($this->manager, 'manager');
        $this->maintenance = $this->makeUser('09123330002', 'تعمیرات');
        $this->maintenanceMembership = $this->membership($this->maintenance, 'maintenance');
    }

    public function test_private_file_is_only_visible_to_uploader_or_authorized_reviewer_in_same_building(): void
    {
        $file = $this->makeFile($this->building, $this->manager);

        $this->actingAs($this->maintenance)->withSession($this->sessionFor($this->maintenanceMembership))
            ->get(route('files.show', $file->id))
            ->assertForbidden();

        $response = $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->get(route('files.show', $file->id))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        // Cache-Control directives are unordered; middleware may append "private" at the end.
        $cacheDirectives = array_map('trim', explode(',', $response->headers->get('Cache-Control', '')));
        sort($cacheDirectives);
        $this->assertSame(['max-age=0', 'no-store', 'private'], $cacheDirectives);
    }

    public function test_private_file_from_another_building_returns_not_found(): void
    {
        $otherBuilding = Building::create([
            'name' => 'برج فایل دیگر',
            'code' => 'FIL002',
            'created_by' => $this->manager->id,
        ]);
        $file = $this->makeFile($otherBuilding, $this->manager);

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->get(route('files.show', $file->id))
            ->assertNotFound();
    }

    private function makeFile(Building $building, User $uploader): File
    {
        $path = 'private-files/'.$building->id.'/receipt.pdf';
        Storage::disk('local')->put($path, 'private receipt contents');

        return File::create([
            'building_id' => $building->id,
            'uploader_id' => $uploader->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'receipt.pdf',
            'mime' => 'application/pdf',
            'size' => 25,
            'visibility' => 'private',
        ]);
    }

    private function makeUser(string $mobile, string $name): User
    {
        return User::create([
            'mobile' => $mobile,
            'first_name' => $name,
            'last_name' => 'تست',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
    }

    private function membership(User $user, string $role): BuildingUserRole
    {
        return BuildingUserRole::create([
            'building_id' => $this->building->id,
            'user_id' => $user->id,
            'role_id' => Role::where('key', $role)->value('id'),
            'status' => 'active',
        ]);
    }

    private function sessionFor(BuildingUserRole $membership): array
    {
        return [
            'current_building_id' => $this->building->id,
            'current_building_user_role_id' => $membership->id,
        ];
    }
}
