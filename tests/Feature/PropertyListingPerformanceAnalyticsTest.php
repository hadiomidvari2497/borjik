<?php

namespace Tests\Feature;

use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PropertyListingPerformanceAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'first_name' => 'مالک',
            'last_name' => 'آزمایشی',
            'mobile' => '09129990001',
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
            'title' => 'آپارتمان تست آمار',
            'description' => 'توضیحات کافی برای آگهی آزمایشی',
            'province' => 'تهران',
            'city' => 'تهران',
            'area' => 90,
            'price' => 9000000000,
            'allow_phone_call' => true,
            'status' => 'active',
        ]);
    }

    public function test_public_listing_views_are_counted_but_owner_views_are_not(): void
    {
        $owner = $this->owner();
        $listing = $this->listing($owner);

        $this->actingAs($owner)->get(route('properties.show', $listing))->assertOk();
        $this->assertDatabaseMissing('property_listing_daily_stats', ['property_listing_id' => $listing->id]);

        auth()->logout();
        $this->get(route('properties.show', $listing))->assertOk();

        $this->assertDatabaseHas('property_listing_daily_stats', [
            'property_listing_id' => $listing->id,
            'stat_date' => now()->toDateString(),
            'views_count' => 1,
        ]);
        $this->assertSame(1, (int) $listing->fresh()->views_count);
    }

    public function test_phone_clicks_are_public_and_counted_once_per_session_per_day(): void
    {
        $listing = $this->listing($this->owner());

        $this->post(route('properties.phone-click', $listing))->assertOk();
        $this->post(route('properties.phone-click', $listing))->assertOk();

        $this->assertDatabaseHas('property_listing_daily_stats', [
            'property_listing_id' => $listing->id,
            'stat_date' => now()->toDateString(),
            'phone_clicks_count' => 1,
        ]);
    }

    public function test_listing_owner_can_filter_leads_and_update_their_status(): void
    {
        $owner = $this->owner();
        $buyer = User::create([
            'first_name' => 'خریدار',
            'last_name' => 'آزمایشی',
            'mobile' => '09129990002',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
        $listing = $this->listing($owner);
        $inquiryId = DB::table('property_listing_inquiries')->insertGetId([
            'property_listing_id' => $listing->id,
            'sender_user_id' => $buyer->id,
            'recipient_user_id' => $owner->id,
            'sender_name' => 'خریدار آزمایشی',
            'message' => 'برای بازدید ملک زمان خالی دارید؟',
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)->get(route('properties.inquiries.index', ['listing_id' => $listing->id, 'status' => 'new']))
            ->assertOk()
            ->assertSee('مدیریت درخواست‌ها و گفتگوها')
            ->assertSee('خریدار آزمایشی')
            ->assertSee('برای بازدید ملک');

        $this->patch(route('properties.inquiries.update', $inquiryId), ['status' => 'agreed'])
            ->assertRedirect();

        $this->assertDatabaseHas('property_listing_inquiries', [
            'id' => $inquiryId,
            'status' => 'agreed',
        ]);
    }

    public function test_listing_owner_can_schedule_and_clear_a_lead_follow_up(): void
    {
        $owner = $this->owner();
        $buyer = User::create([
            'first_name' => 'خریدار',
            'last_name' => 'یادآوری',
            'mobile' => '09129990004',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
        $listing = $this->listing($owner);
        $inquiryId = DB::table('property_listing_inquiries')->insertGetId([
            'property_listing_id' => $listing->id,
            'sender_user_id' => $buyer->id,
            'recipient_user_id' => $owner->id,
            'sender_name' => 'خریدار یادآوری',
            'message' => 'برای بازدید تماس بگیرید',
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $followUpAt = now()->addDay()->startOfMinute();

        $this->actingAs($owner)
            ->patch(route('properties.inquiries.update', $inquiryId), [
                'follow_up_at' => $followUpAt->format('Y-m-d\\TH:i'),
                'follow_up_note' => 'تماس برای هماهنگی بازدید',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('property_listing_inquiries', [
            'id' => $inquiryId,
            'follow_up_note' => 'تماس برای هماهنگی بازدید',
        ]);
        $this->assertEquals(
            $followUpAt->timestamp,
            $this->assertDatabaseHas('property_listing_inquiries', ['id' => $inquiryId]) ? strtotime(DB::table('property_listing_inquiries')->where('id', $inquiryId)->value('follow_up_at')) : 0
        );

        $this->actingAs($owner)
            ->patch(route('properties.inquiries.update', $inquiryId), ['follow_up_at' => '', 'follow_up_note' => ''])
            ->assertRedirect();

        $this->assertDatabaseHas('property_listing_inquiries', [
            'id' => $inquiryId,
            'follow_up_at' => null,
            'follow_up_note' => null,
        ]);
    }

    public function test_only_listing_owner_can_change_a_lead_status(): void
    {
        $owner = $this->owner();
        $buyer = User::create([
            'first_name' => 'خریدار',
            'last_name' => 'دوم',
            'mobile' => '09129990003',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
        $listing = $this->listing($owner);
        $inquiryId = DB::table('property_listing_inquiries')->insertGetId([
            'property_listing_id' => $listing->id,
            'sender_user_id' => $buyer->id,
            'recipient_user_id' => $owner->id,
            'sender_name' => 'خریدار دوم',
            'message' => 'سلام',
            'status' => 'new',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($buyer)
            ->patch(route('properties.inquiries.update', $inquiryId), ['status' => 'closed'])
            ->assertForbidden();

        $this->assertDatabaseHas('property_listing_inquiries', ['id' => $inquiryId, 'status' => 'new']);
    }

    public function test_owner_dashboard_shows_performance_totals_and_daily_activity(): void
    {
        $owner = $this->owner();
        $listing = $this->listing($owner);
        DB::table('property_listings')->where('id', $listing->id)->update(['views_count' => 7]);
        DB::table('property_listing_daily_stats')->insert([
            'property_listing_id' => $listing->id,
            'stat_date' => now()->toDateString(),
            'views_count' => 7,
            'phone_clicks_count' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($owner)->get(route('properties.mine'))
            ->assertOk()
            ->assertSee('عملکرد آگهی‌های شما')
            ->assertSee('بازدید آگهی‌ها')
            ->assertSee('کلیک روی تماس')
            ->assertSee('گفتگوهای شروع‌شده')
            ->assertSee('روند بازدید روزانه');
    }
}
