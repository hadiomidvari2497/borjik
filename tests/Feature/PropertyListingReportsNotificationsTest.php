<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\PropertyListing;
use App\Models\PropertyListingReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyListingReportsNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $reporter;
    private PropertyListing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = $this->user('09120000031', 'مالک');
        $this->reporter = $this->user('09120000032', 'گزارش‌دهنده');
        $this->listing = PropertyListing::create([
            'user_id' => $this->owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آگهی آزمایشی بازارچه',
            'description' => 'توضیحات آگهی',
            'city' => 'تهران',
            'status' => 'active',
        ]);
    }

    private function user(string $mobile, string $firstName): User
    {
        return User::create([
            'mobile' => $mobile,
            'first_name' => $firstName,
            'last_name' => 'تست',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
    }

    private function admin(): AdminUser
    {
        return AdminUser::create([
            'username' => 'property-report-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);
    }

    public function test_users_can_report_another_users_listing_but_not_their_own_or_duplicate_pending_reports(): void
    {
        $payload = ['reason' => 'incorrect', 'details' => 'قیمت درج‌شده با توضیحات تناقض دارد.'];

        $this->actingAs($this->reporter)
            ->post(route('properties.reports.store', $this->listing->id), $payload)
            ->assertRedirect(route('properties.show', $this->listing->id));

        $report = PropertyListingReport::firstOrFail();
        $this->assertSame('pending', $report->status);
        $this->assertSame($this->reporter->id, $report->reporter_user_id);

        $this->actingAs($this->reporter)
            ->from(route('properties.show', $this->listing->id))
            ->post(route('properties.reports.store', $this->listing->id), $payload)
            ->assertSessionHasErrors('report');

        $ownListing = PropertyListing::create([
            'user_id' => $this->reporter->id,
            'listing_type' => 'rent',
            'property_type' => 'residential',
            'title' => 'آگهی خودم',
            'description' => 'توضیحات',
            'city' => 'تهران',
            'status' => 'active',
        ]);

        $this->actingAs($this->reporter)
            ->post(route('properties.reports.store', $ownListing->id), $payload)
            ->assertForbidden();

        $this->assertDatabaseCount('property_listing_reports', 1);
    }

    public function test_admin_report_review_notifies_reporter_and_personal_notifications_work_without_a_building(): void
    {
        $admin = $this->admin();
        $report = PropertyListingReport::create([
            'property_listing_id' => $this->listing->id,
            'reporter_user_id' => $this->reporter->id,
            'reason' => 'fraud',
            'details' => 'نشانه‌های کلاهبرداری مشاهده شد.',
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.property-listings.reports.update', $report->id), [
                'status' => 'actioned',
                'resolution_note' => 'گزارش تأیید شد و آگهی برای بررسی بیشتر غیرفعال شد.',
            ])
            ->assertRedirect(route('admin.property-listings.reports', ['status' => 'actioned']));

        $this->assertDatabaseHas('property_listing_reports', [
            'id' => $report->id,
            'status' => 'actioned',
            'reviewed_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('property_listings', [
            'id' => $this->listing->id,
            'status' => 'closed',
            'moderated_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->owner->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $this->reporter->id,
        ]);

        $this->actingAs($this->reporter)
            ->get(route('notifications.index', ['category' => 'marketplace']))
            ->assertOk()
            ->assertSee('بازارچه املاک')
            ->assertSee('گزارش تخلف بررسی شد')
            ->assertSee('گزارش تأیید شد و آگهی برای بررسی بیشتر غیرفعال شد.');

        $notification = $this->reporter->notifications()->firstOrFail();
        $this->actingAs($this->reporter)
            ->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('properties.mine'));

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_verified_mobile_badge_is_shown_on_listing_detail(): void
    {
        $this->owner->forceFill(['mobile_verified_at' => now()])->save();

        $this->get(route('properties.show', $this->listing->id))
            ->assertOk()
            ->assertSee('شماره موبایل تأییدشده');
    }

    public function test_listing_owner_receives_notification_when_admin_changes_listing_status(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')
            ->patch(route('admin.property-listings.status', $this->listing->id), [
                'status' => 'rejected',
                'moderation_note' => 'مدارک مالکیت برای تأیید کافی نیست.',
            ])
            ->assertRedirect(route('admin.property-listings.show', $this->listing->id));

        $notification = $this->owner->notifications()->firstOrFail();
        $this->assertSame('marketplace', $notification->data['category']);
        $this->assertSame('listing_status_changed', $notification->data['event']);
        $this->assertStringContainsString('مدارک مالکیت', $notification->data['message']);

        $this->actingAs($this->owner)
            ->get(route('notifications.index', ['category' => 'marketplace']))
            ->assertOk()
            ->assertSee('آگهی شما رد شد')
            ->assertSee('مدارک مالکیت');
    }
}
