<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\SmsTemplate;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmsTemplateTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->owner = User::create(['mobile' => '09120000041', 'first_name' => 'مالک', 'last_name' => 'پیامک', 'status' => 'active', 'profile_completed_at' => now()]);
        $this->building = Building::create(['name' => 'ساختمان پیامک', 'code' => '88141', 'created_by' => $this->owner->id]);
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

    public function test_owner_can_create_update_and_delete_building_template(): void
    {
        $this->open('post', route('sms-templates.store'), [
            'key' => 'invitation',
            'body' => 'دعوت به ساختمان {building} از طرف {inviter}',
            'is_active' => '1',
        ])->assertRedirect(route('sms-templates.index'));

        $template = SmsTemplate::where('building_id', $this->building->id)->where('key', 'invitation')->firstOrFail();
        $this->assertTrue($template->is_active);

        $this->open('put', route('sms-templates.update', $template->id), [
            'body' => 'دعوت جدید {building}',
            'is_active' => '1',
        ])->assertRedirect(route('sms-templates.index'));
        $this->assertSame('دعوت جدید {building}', $template->fresh()->body);

        $this->open('delete', route('sms-templates.destroy', $template->id))
            ->assertRedirect(route('sms-templates.index'));
        $this->assertDatabaseMissing('sms_templates', ['id' => $template->id]);
    }

    public function test_template_from_another_building_cannot_be_updated(): void
    {
        $other = Building::create(['name' => 'ساختمان دیگر', 'code' => '88142', 'created_by' => $this->owner->id]);
        $otherTemplateId = app(\App\Support\CurrentBuilding::class)->actingAs($other->id, function () {
            return SmsTemplate::create(['building_id' => $other->id, 'key' => 'invitation', 'body' => 'قالب دیگر', 'is_active' => true])->id;
        });

        $this->open('put', route('sms-templates.update', $otherTemplateId), [
            'body' => 'تغییر غیرمجاز',
            'is_active' => '1',
        ])->assertNotFound();

        $this->assertDatabaseHas('sms_templates', ['id' => $otherTemplateId, 'body' => 'قالب دیگر']);
    }
}
