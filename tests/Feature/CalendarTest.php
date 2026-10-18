<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\CalendarEvent;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\RolePermissionSeeder;
use App\Support\JalaliDate;
use App\Notifications\CalendarEventReminderNotification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;

class CalendarTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private User $owner;
    private User $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = $this->makeUser('09120000021', 'owner');
        $this->building = Building::create([
            'name' => 'ساختمان تقویم',
            'code' => '66221',
            'created_by' => $this->owner->id,
        ]);
        $this->join($this->owner, 'owner');

        $this->tenant = $this->makeUser('09120000022', 'tenant');
        $this->join($this->tenant, 'tenant');
    }

    private function makeUser(string $mobile, string $role): User
    {
        return User::create([
            'mobile' => $mobile,
            'first_name' => 'کاربر',
            'last_name' => $role,
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
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
        return $this->actingAs($user)
            ->withSession(['current_building_id' => $this->building->id])
            ->{$method}($uri, $data);
    }

    public function test_calendar_renders_for_members_with_view_permission(): void
    {
        $this->open($this->tenant, 'get', route('events.index'))
            ->assertOk()
            ->assertSee('تقویم ساختمان')
            ->assertSee('همه‌چیز سرِ وقت');
    }

    public function test_manager_can_create_a_jalali_calendar_event(): void
    {
        $this->open($this->owner, 'post', route('events.store'), [
            'title' => 'جلسه ماهانه ساختمان',
            'description' => 'بررسی برنامه‌های ماه',
            'start_date' => JalaliDate::format(now()),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'color' => 'teal',
        ])->assertRedirect();

        $this->assertDatabaseHas('calendar_events', [
            'building_id' => $this->building->id,
            'created_by' => $this->owner->id,
            'title' => 'جلسه ماهانه ساختمان',
        ]);
    }

    public function test_tenant_cannot_create_calendar_events(): void
    {
        $this->open($this->tenant, 'post', route('events.store'), [
            'title' => 'رویداد غیرمجاز',
            'start_date' => JalaliDate::format(now()),
            'color' => 'teal',
        ])->assertForbidden();

        $this->assertDatabaseCount('calendar_events', 0);
    }

    public function test_manager_can_delete_an_event_from_their_building(): void
    {
        $event = app(\App\Support\CurrentBuilding::class)->actingAs(
            $this->building->id,
            fn () => CalendarEvent::create([
                'created_by' => $this->owner->id,
                'title' => 'رویداد آزمایشی',
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDay()->addHour(),
                'is_all_day' => false,
                'color' => 'teal',
            ]),
            ['owner'],
        );

        $this->open($this->owner, 'delete', route('events.destroy', $event->id))
            ->assertRedirect();

        $this->assertDatabaseMissing('calendar_events', ['id' => $event->id]);
    }

    public function test_manager_can_update_a_calendar_event(): void
    {
        $event = app(\App\Support\CurrentBuilding::class)->actingAs(
            $this->building->id,
            fn () => CalendarEvent::create([
                'created_by' => $this->owner->id,
                'title' => 'عنوان قبلی',
                'starts_at' => now()->addDay(),
                'ends_at' => now()->addDay()->addHour(),
                'is_all_day' => false,
                'color' => 'teal',
            ]),
            ['owner'],
        );

        $this->open($this->owner, 'put', route('events.update', $event->id), [
            'title' => 'عنوان ویرایش‌شده',
            'description' => 'توضیحات جدید',
            'start_date' => JalaliDate::format(now()->addDays(2)),
            'start_time' => '12:00',
            'end_time' => '13:00',
            'color' => 'violet',
            'reminder_minutes' => '60',
        ])->assertRedirect();

        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'title' => 'عنوان ویرایش‌شده',
            'color' => 'violet',
            'reminder_minutes' => 60,
        ]);
    }

    public function test_calendar_supports_category_filters(): void
    {
        $this->open($this->tenant, 'get', route('events.index', ['filter' => 'tasks']))
            ->assertOk()
            ->assertSee('مهلت تسک‌ها')
            ->assertSee('cal-filter active', false);
    }


    public function test_calendar_reminder_notifies_creator_once_when_due(): void
    {
        Notification::fake();

        $event = app(\App\Support\CurrentBuilding::class)->actingAs(
            $this->building->id,
            fn () => CalendarEvent::create([
                'created_by' => $this->owner->id,
                'title' => 'جلسه یادآوری',
                'starts_at' => now()->addMinutes(10),
                'ends_at' => now()->addMinutes(70),
                'is_all_day' => false,
                'color' => 'teal',
                'reminder_minutes' => 15,
            ]),
            ['owner'],
        );

        Artisan::call('calendar:send-reminders');

        Notification::assertSentTo($this->owner, CalendarEventReminderNotification::class);
        $this->assertNotNull($event->fresh()->reminder_sent_at);

        Artisan::call('calendar:send-reminders');
        Notification::assertSentToTimes($this->owner, CalendarEventReminderNotification::class, 1);
    }


    public function test_calendar_renders_week_and_day_views(): void
    {
        $date = JalaliDate::format(now());

        $this->open($this->tenant, 'get', route('events.index', ['view' => 'week', 'date' => $date]))
            ->assertOk()
            ->assertSee('mode-week', false)
            ->assertSee('نمای هفتگی');

        $this->open($this->tenant, 'get', route('events.index', ['view' => 'day', 'date' => $date]))
            ->assertOk()
            ->assertSee('mode-day', false)
            ->assertSee('برنامه روزانه');
    }

    public function test_manager_can_move_calendar_event_to_another_jalali_date(): void
    {
        $event = app(\App\Support\CurrentBuilding::class)->actingAs(
            $this->building->id,
            fn () => CalendarEvent::create([
                'created_by' => $this->owner->id,
                'title' => 'جلسه قابل جابه‌جایی',
                'starts_at' => now()->addDay()->setTime(9, 0),
                'ends_at' => now()->addDay()->setTime(10, 0),
                'is_all_day' => false,
                'color' => 'teal',
            ]),
            ['owner'],
        );
        $targetDate = JalaliDate::format(now()->addDays(4));
        $target = JalaliDate::parse($targetDate)->setTimeFromTimeString('14:00');

        $this->open($this->owner, 'patch', route('events.move', $event->id), [
            'start_date' => $targetDate,
            'start_time' => '14:00',
            'view' => 'week',
            'filter' => 'all',
        ])->assertRedirect();

        $this->assertDatabaseHas('calendar_events', [
            'id' => $event->id,
            'starts_at' => $target->format('Y-m-d H:i:s'),
        ]);
    }

}
