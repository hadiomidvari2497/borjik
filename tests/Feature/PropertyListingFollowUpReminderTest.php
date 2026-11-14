<?php

namespace Tests\\Feature;

use App\\Models\\PropertyListing;
use App\\Models\\PropertyListingInquiry;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\Artisan;
use Tests\\TestCase;

class PropertyListingFollowUpReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_lead_follow_up_notifies_the_listing_owner_only_once(): void
    {
        $owner = $this->user('09121110001');
        $buyer = $this->user('09121110002');
        $listing = $this->listing($owner);
        $inquiry = $this->inquiry($owner, $buyer, $listing, now()->subMinute());

        Artisan::call('properties:send-follow-up-reminders');

        $this->assertDatabaseCount('notifications', 1);
        $notification = $owner->notifications()->firstOrFail();
        $this->assertSame('marketplace', $notification->data['category']);
        $this->assertSame('lead_follow_up_due', $notification->data['event']);
        $this->assertSame(route('properties.chat.show', $inquiry), $notification->data['url']);
        $this->assertStringContainsString('برای بازدید تماس بگیرید', $notification->data['message']);
        $this->assertNotNull($inquiry->fresh()->follow_up_notified_at);

        Artisan::call('properties:send-follow-up-reminders');

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_future_and_closed_follow_ups_are_not_notified(): void
    {
        $owner = $this->user('09121110003');
        $buyer = $this->user('09121110004');
        $listing = $this->listing($owner);

        $future = $this->inquiry($owner, $buyer, $listing, now()->addHour());
        $closed = $this->inquiry($owner, $this->user('09121110005'), $listing, now()->subMinute(), 'closed');

        Artisan::call('properties:send-follow-up-reminders');

        $this->assertDatabaseCount('notifications', 0);
        $this->assertNull($future->fresh()->follow_up_notified_at);
        $this->assertNull($closed->fresh()->follow_up_notified_at);
    }

    public function test_changing_a_follow_up_time_allows_a_new_reminder_to_be_sent(): void
    {
        $owner = $this->user('09121110006');
        $buyer = $this->user('09121110007');
        $listing = $this->listing($owner);
        $inquiry = $this->inquiry($owner, $buyer, $listing, now()->subMinute());

        Artisan::call('properties:send-follow-up-reminders');
        $this->assertDatabaseCount('notifications', 1);

        $this->actingAs($owner)
            ->patch(route('properties.inquiries.update', $inquiry), [
                'follow_up_at' => now()->subSeconds(30)->format('Y-m-d\\TH:i'),
                'follow_up_note' => 'تماس مجدد',
            ])
            ->assertRedirect();

        $this->assertNull($inquiry->fresh()->follow_up_notified_at);

        Artisan::call('properties:send-follow-up-reminders');

        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_inquiry_dashboard_shows_due_and_overdue_follow_ups_to_the_listing_owner(): void
    {
        $owner = $this->user('09121110008');
        $buyer = $this->user('09121110009');
        $listing = $this->listing($owner);
        $this->inquiry($owner, $buyer, $listing, now()->subHour());

        $this->actingAs($owner)
            ->get(route('properties.inquiries.index'))
            ->assertOk()
            ->assertSee('پیگیری‌های امروز')
            ->assertSee('عقب‌افتاده')
            ->assertSee($buyer->display_name)
            ->assertSee($listing->title);
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

    private function listing(User $owner): PropertyListing
    {
        return PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آپارتمان جهت فروش',
            'description' => 'توضیحات کامل ملک',
            'city' => 'تهران',
            'status' => 'active',
        ]);
    }

    private function inquiry(User $owner, User $buyer, PropertyListing $listing, $followUpAt, string $status = 'contacted'): PropertyListingInquiry
    {
        return PropertyListingInquiry::create([
            'property_listing_id' => $listing->id,
            'sender_user_id' => $buyer->id,
            'recipient_user_id' => $owner->id,
            'sender_name' => $buyer->display_name,
            'message' => 'برای بازدید ملک پیام دارم.',
            'status' => $status,
            'follow_up_at' => $followUpAt,
            'follow_up_note' => 'برای بازدید تماس بگیرید',
        ]);
    }
}
