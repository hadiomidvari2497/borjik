<?php

namespace Tests\\Feature;

use App\\Models\\PropertyListing;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\DB;
use Tests\\TestCase;

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
