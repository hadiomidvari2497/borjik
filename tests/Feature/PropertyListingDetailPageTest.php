<?php

namespace Tests\Feature;

use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyListingDetailPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $mobile, string $name = 'فروشنده'): User
    {
        return User::create(['first_name' => $name, 'last_name' => 'آزمایشی', 'mobile' => $mobile, 'status' => 'active', 'profile_completed_at' => now()]);
    }

    private function saleListing(User $owner, array $overrides = []): PropertyListing
    {
        return PropertyListing::create(array_merge([
            'user_id' => $owner->id, 'listing_type' => 'sale', 'property_type' => 'residential',
            'title' => 'آپارتمان ۹۰ متری', 'description' => str_repeat('توضیح بلند برای تست. ', 40),
            'province' => 'تهران', 'city' => 'تهران', 'neighborhood' => 'ونک',
            'address' => 'خیابان نمونه، پلاک ۱۲', 'hide_address' => true,
            'area' => 90, 'bedrooms' => 2, 'floor' => 3, 'total_floors' => 5, 'price' => 9000000000,
            'show_phone' => true, 'amenities' => ['آسانسور', 'انباری'],
            'photos' => ['property-listings/a.jpg', 'property-listings/b.jpg', 'property-listings/c.jpg'],
            'status' => 'active',
        ], $overrides));
    }

    public function test_detail_page_shows_the_new_sections(): void
    {
        $owner = $this->makeUser('09121110001');
        $listing = $this->saleListing($owner);
        PropertyListing::create(['user_id' => $owner->id, 'listing_type' => 'sale', 'property_type' => 'residential', 'title' => 'آگهی مشابه یک', 'description' => 'x', 'city' => 'تهران', 'price' => 5000000000, 'status' => 'active']);
        PropertyListing::create(['user_id' => $owner->id, 'listing_type' => 'rent', 'property_type' => 'residential', 'title' => 'اجاره غیرمشابه', 'description' => 'x', 'city' => 'تهران', 'status' => 'active']);
        PropertyListing::create(['user_id' => $owner->id, 'listing_type' => 'sale', 'property_type' => 'residential', 'title' => 'آگهی بسته‌شده', 'description' => 'x', 'city' => 'تهران', 'status' => 'closed']);

        $this->get(route('properties.show', $listing))
            ->assertOk()
            ->assertSee('data-lightbox', false)
            ->assertSee('نکات ایمنی معامله')
            ->assertSee('قیمت هر متر')
            ->assertSee('آسانسور')
            ->assertSee('کد آگهی')
            ->assertSee('آگهی‌های مشابه')
            ->assertSee('آگهی مشابه یک')
            ->assertDontSee('اجاره غیرمشابه')
            ->assertDontSee('آگهی بسته‌شده')
            ->assertDontSee('خیابان نمونه، پلاک ۱۲');
    }

    public function test_structured_data_is_valid_json_and_escapes_markup(): void
    {
        $listing = $this->saleListing($this->makeUser('09121110001'), ['title' => 'عنوان </script><b>x</b>']);

        $html = $this->get(route('properties.show', $listing))->assertOk()->getContent();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $m);

        $data = json_decode($m[1] ?? '', true);
        $this->assertSame(JSON_ERROR_NONE, json_last_error());
        $this->assertSame('RealEstateListing', $data['@type']);
        $this->assertCount(3, $data['image']);
        $this->assertStringNotContainsString('خیابان نمونه', $m[1]);
        $this->assertStringNotContainsString('</script><b>', explode('</head>', $html)[0]);
    }

    public function test_viewer_roles_and_phone_privacy(): void
    {
        $owner = $this->makeUser('09121110001');
        $visitor = $this->makeUser('09121110002', 'بازدیدکننده');
        $listing = $this->saleListing($owner);
        $hidden = $this->saleListing($owner, ['title' => 'شماره پنهان', 'show_phone' => false]);

        $this->get(route('properties.show', $listing))->assertSee('tel:09121110001', false)->assertSee('برای گفتگو با فروشنده وارد شوید');
        $this->actingAs($visitor)->get(route('properties.show', $listing))->assertSee('شروع گفتگو با فروشنده')->assertSee('گزارش تخلف یا اطلاعات نادرست');
        $this->actingAs($owner)->get(route('properties.show', $listing))->assertSee('این آگهی متعلق به شماست.')->assertDontSee('گزارش تخلف یا اطلاعات نادرست');
        $this->get(route('properties.show', $hidden))->assertOk()->assertDontSee('tel:09121110001', false)->assertDontSee('sms:09121110001', false);
    }

    public function test_listing_without_photos_has_no_gallery_or_lightbox_and_rent_has_no_per_meter(): void
    {
        $listing = $this->saleListing($this->makeUser('09121110001'), ['listing_type' => 'rent', 'price' => null, 'deposit' => 500000000, 'monthly_rent' => 10000000, 'photos' => null, 'description' => 'کوتاه']);

        $this->get(route('properties.show', $listing))
            ->assertOk()
            ->assertDontSee('data-gallery', false)
            ->assertDontSee('data-lightbox', false)
            ->assertDontSee('قیمت هر متر')
            ->assertDontSee('نمایش بیشتر')
            ->assertSee('تصویری ثبت نشده');
    }
}
