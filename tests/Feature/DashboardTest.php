<?php

namespace Tests\Feature;

use App\Models\Block;
use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\Unit;
use App\Models\Ticket;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** فرض می‌کند routes/auth.php و routes/app.php در web.php لود شده‌اند (README). */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $creator = $this->user('09120000001', 'owner');
        $this->building = Building::create(['name' => 'برج آسمان', 'code' => '11111', 'created_by' => $creator->id]);
        BuildingUserRole::create(['building_id' => $this->building->id, 'user_id' => $creator->id, 'role_id' => Role::where('key', 'owner')->value('id'), 'status' => 'active']);

        app(CurrentBuilding::class)->actingAs($this->building->id, function () {
            $block = Block::create(['number' => 1, 'name' => 'الف', 'floors_count' => 5]);
            Unit::create(['block_id' => $block->id, 'number' => '1', 'area_m2' => 100, 'status' => 'vacant']);
            Unit::create(['block_id' => $block->id, 'number' => '2', 'area_m2' => 80, 'status' => 'sold', 'residents_count' => 3]);
        });
    }

    private function user(string $mobile, string $role): User
    {
        return User::create(['mobile' => $mobile, 'first_name' => 'کاربر', 'last_name' => $role, 'status' => 'active', 'profile_completed_at' => now()]);
    }

    private function join(User $user, string $role): void
    {
        BuildingUserRole::create(['building_id' => $this->building->id, 'user_id' => $user->id, 'role_id' => Role::where('key', $role)->value('id'), 'status' => 'active']);
    }

    private function open(User $user)
    {
        return $this->actingAs($user)->withSession(['current_building_id' => $this->building->id])->get('/');
    }

    public function test_owner_sees_management_dashboard_with_full_menu(): void
    {
        $owner = User::where('mobile', '09120000001')->firstOrFail();

        $this->open($owner)->assertOk()
            ->assertSee('برج آسمان')
            ->assertSee('وضعیت واحدها')
            ->assertSee('تنظیمات شارژ');
    }

    public function test_tenant_gets_resident_dashboard_and_a_filtered_menu(): void
    {
        $tenant = $this->user('09120000002', 'tenant');
        $this->join($tenant, 'tenant');

        $this->open($tenant)->assertOk()
            ->assertSee('مانده‌ی بدهی')
            ->assertDontSee('تنظیمات شارژ')
            ->assertDontSee('وضعیت واحدها');
    }

    public function test_security_guard_sees_task_dashboard(): void
    {
        $guard = $this->user('09120000003', 'security');
        $this->join($guard, 'security');

        $this->open($guard)->assertOk()->assertSee('تسک‌های ارجاع‌شده به من')->assertDontSee('پرداخت‌ها');
    }

    public function test_user_without_profile_is_sent_to_profile_page(): void
    {
        $user = User::create(['mobile' => '09120000004', 'status' => 'active']);
        $this->join($user, 'tenant');

        $this->open($user)->assertRedirect(route('profile.edit'));
    }

    public function test_user_cannot_open_a_building_they_do_not_belong_to(): void
    {
        $outsider = $this->user('09120000005', 'tenant');
        $other = Building::create(['name' => 'برج دیگر', 'code' => '22222', 'created_by' => $outsider->id]);
        $this->join($outsider, 'tenant');

        // session به ساختمانی اشاره می‌کند که عضو آن نیست؛ Middleware باید به ساختمان خودش برگرداند.
        $this->actingAs($outsider)->withSession(['current_building_id' => $other->id])->get('/')
            ->assertOk()->assertSee('برج آسمان')->assertDontSee('برج دیگر');
    }

    public function test_user_without_any_building_sees_the_welcome_page_not_a_403(): void
    {
        $newcomer = $this->user('09120000006', 'tenant');

        $this->actingAs($newcomer)->get('/')->assertOk()
            ->assertSee('امکانات برجیک')
            ->assertSee('تهیه‌ی اشتراک')
            ->assertSee('محاسبه شارژ'); // آیتم قفل‌شده‌ی منوی پیش‌نمایش
    }

    public function test_other_pages_redirect_a_user_without_building_to_the_dashboard(): void
    {
        $newcomer = $this->user('09120000007', 'tenant');

        $this->actingAs($newcomer)->get(route('units.index'))->assertRedirect(route('dashboard'));
    }

    public function test_management_dashboard_supports_selectable_trend_ranges(): void
    {
        $owner = User::where('mobile', '09120000001')->firstOrFail();

        $this->actingAs($owner)->withSession(['current_building_id' => $this->building->id])
            ->get('/?trend_days=7')->assertOk()
            ->assertSee('روند درخواست‌ها، بدهی و وصولی')
            ->assertSee('۷ روز')
            ->assertSee('۳۰ روز')
            ->assertSee('۹۰ روز')
            ->assertSee('بدهی سررسیدشده در بازه')
            ->assertSee('وصولی‌های تأییدشده');

        $this->actingAs($owner)->withSession(['current_building_id' => $this->building->id])
            ->get('/?trend_days=90')->assertOk()
            ->assertSee('روند درخواست‌ها، بدهی و وصولی');

        $this->actingAs($owner)->withSession(['current_building_id' => $this->building->id])
            ->get('/?trend_days=999')->assertOk()
            ->assertSee('۳۰ روز');
    }

    public function test_management_dashboard_highlights_overdue_tasks_and_urgent_tickets(): void
    {
        $owner = User::where('mobile', '09120000001')->firstOrFail();

        app(CurrentBuilding::class)->actingAs($this->building->id, function () use ($owner): void {
            Ticket::create([
                'number' => 'DASH-OVERDUE-1',
                'kind' => 'task',
                'title' => 'تعمیر عقب‌افتاده',
                'category' => 'maintenance',
                'priority' => 'high',
                'deadline' => now()->subDay(),
                'status' => 'doing',
                'created_by' => $owner->id,
            ]);

            Ticket::create([
                'number' => 'DASH-URGENT-1',
                'kind' => 'request',
                'title' => 'درخواست اضطراری',
                'category' => 'maintenance',
                'priority' => 'urgent',
                'status' => 'new',
                'created_by' => $owner->id,
            ]);
        });

        $this->open($owner)->assertOk()
            ->assertSee('مرکز رسیدگی هوشمند')
            ->assertSee('کارهای عقب‌افتاده')
            ->assertSee('موارد اضطراری باز')
            ->assertSee('رسیدگی');
    }
}
