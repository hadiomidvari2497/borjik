<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Document;
use App\Models\File as StoredFile;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentArchiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_upload_and_download_a_private_document_in_the_current_building(): void
    {
        Storage::fake('local');
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $manager = $this->user('09120000001');
        $building = Building::create(['name' => 'ساختمان اسناد', 'code' => 'DOC001', 'created_by' => $manager->id]);
        $membership = $this->membership($manager, $building, 'manager');

        $response = $this->actingAs($manager)->withSession($this->sessionFor($building, $membership))
            ->post(route('documents.store'), [
                'title' => 'آیین‌نامه ساختمان',
                'category' => 'regulations',
                'description' => 'نسخه مصوب',
                'file' => UploadedFile::fake()->create('regulations.pdf', 30, 'application/pdf'),
            ]);

        $response->assertRedirect(route('documents.index'));
        $document = app(\\App\\Support\\CurrentBuilding::class)->actingAs($building->id, fn () => Document::query()->firstOrFail());
        $this->assertSame($building->id, $document->building_id);
        $this->assertNotNull($document->file_id);

        $this->actingAs($manager)->withSession($this->sessionFor($building, $membership))
            ->get(route('documents.download', $document->id))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_document_from_another_building_cannot_be_downloaded(): void
    {
        Storage::fake('local');
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);
        $manager = $this->user('09120000002');
        $building = Building::create(['name' => 'ساختمان اول', 'code' => 'DOC002', 'created_by' => $manager->id]);
        $otherBuilding = Building::create(['name' => 'ساختمان دوم', 'code' => 'DOC003', 'created_by' => $manager->id]);
        $membership = $this->membership($manager, $building, 'manager');
        $otherPath = 'private-documents/'.$otherBuilding->id.'/other.pdf';
        Storage::disk('local')->put($otherPath, 'secret');
        $file = StoredFile::create([
            'building_id' => $otherBuilding->id, 'uploader_id' => $manager->id, 'disk' => 'local',
            'path' => $otherPath, 'original_name' => 'other.pdf', 'mime' => 'application/pdf',
            'size' => 6, 'visibility' => 'private',
        ]);
        $document = app(\App\Support\CurrentBuilding::class)->actingAs($otherBuilding->id, fn () => Document::create([
            'building_id' => $otherBuilding->id, 'title' => 'محرمانه', 'category' => 'other',
            'uploaded_by' => $manager->id, 'file_id' => $file->id,
        ]));

        $this->actingAs($manager)->withSession($this->sessionFor($building, $membership))
            ->get(route('documents.download', $document->id))
            ->assertNotFound();
    }

    private function user(string $mobile): User
    {
        return User::create(['mobile' => $mobile, 'first_name' => 'کاربر', 'last_name' => 'آزمایشی', 'status' => 'active', 'profile_completed_at' => now()]);
    }

    private function membership(User $user, Building $building, string $role): BuildingUserRole
    {
        return BuildingUserRole::create([
            'building_id' => $building->id, 'user_id' => $user->id,
            'role_id' => Role::where('key', $role)->value('id'), 'status' => 'active',
        ]);
    }

    private function sessionFor(Building $building, BuildingUserRole $membership): array
    {
        return ['current_building_id' => $building->id, 'current_building_user_role_id' => $membership->id];
    }
}
