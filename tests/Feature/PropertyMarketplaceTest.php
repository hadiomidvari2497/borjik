<?php

namespace Tests\Feature;

use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyMarketplaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_marketplace_shows_only_active_listings(): void
    {
        $owner = $this->makeUser();

        $active = PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'واحد دوخوابه',
            'description' => 'توضیحات آگهی',
            'city' => 'تهران',
            'status' => 'active',
        ]);

        PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'rent',
            'property_type' => 'residential',
            'title' => 'آگهی بسته‌شده',
            'description' => 'توضیحات',
            'city' => 'تهران',
            'status' => 'closed',
        ]);

        $this->get(route('properties.index'))
            ->assertOk()
            ->assertSee($active->title)
            ->assertDontSee('آگهی بسته‌شده');
    }

    public function test_users_without_building_membership_can_still_open_the_standalone_listing_form(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('properties.create'))
            ->assertOk()
            ->assertSee('آگهی مستقل (بدون اتصال به واحد)');
    }

    public function test_users_can_publish_three_free_active_listings_but_not_a_fourth(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $payload = [
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آگهی آزمایشی',
            'description' => 'توضیحات کافی برای آگهی آزمایشی',
            'city' => 'تهران',
            'price' => 5000000000,
        ];

        foreach (range(1, 3) as $number) {
            $this->post(route('properties.store'), array_merge($payload, ['title' => $payload['title'].' '.$number]))
                ->assertRedirect(route('properties.mine'));
        }

        $this->post(route('properties.store'), array_merge($payload, ['title' => 'آگهی چهارم']))
            ->assertForbidden();

        $this->assertDatabaseCount('property_listings', 3);
    }

    public function test_listing_owner_can_close_listing_but_another_user_cannot(): void
    {
        $owner = $this->makeUser();
        $other = $this->makeUser();
        $listing = PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آگهی مالک',
            'description' => 'توضیحات',
            'city' => 'تهران',
            'status' => 'active',
        ]);

        $this->actingAs($other)->post(route('properties.close', $listing))->assertForbidden();
        $this->actingAs($owner)->post(route('properties.close', $listing))->assertRedirect();
        $this->assertDatabaseHas('property_listings', ['id' => $listing->id, 'status' => 'closed']);
    }

    public function test_listing_page_renders_photos_as_a_slider_with_price_rows(): void
    {
        $owner = $this->makeUser();
        $listing = PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'rent',
            'property_type' => 'residential',
            'title' => 'آپارتمان نورگیر',
            'description' => 'توضیحات',
            'city' => 'تهران',
            'area' => 85,
            'bedrooms' => 2,
            'deposit' => 800000000,
            'monthly_rent' => 12000000,
            'photos' => ['property-listings/a.jpg', 'property-listings/b.jpg', 'property-listings/c.jpg'],
            'status' => 'active',
        ]);

        $this->get(route('properties.show', $listing))
            ->assertOk()
            ->assertSee('data-gallery', false)
            ->assertSee('storage/property-listings/b.jpg', false)
            ->assertSee('class="nav-btn next"', false)
            ->assertSee('ودیعه')
            ->assertSee('اجاره ماهانه')
            ->assertSee('۱۲٬۰۰۰٬۰۰۰ تومان', false);

        $this->get(route('properties.index', ['listing_type' => 'rent']))
            ->assertOk()
            ->assertSee('آپارتمان نورگیر')
            ->assertSee('۸۰۰ میلیون تومان', false);

        $listing->update(['photos' => null]);
        $this->get(route('properties.show', $listing))->assertOk()->assertDontSee('data-gallery', false);
    }

    public function test_marketplace_search_filters_by_keyword_area_price_bedrooms_and_parking(): void
    {
        $owner = $this->makeUser();

        $matching = PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آپارتمان نورگیر',
            'description' => 'واحد خوش‌نقشه',
            'city' => 'تهران',
            'area' => 120,
            'bedrooms' => 2,
            'parking_count' => 1,
            'price' => 8000000000,
            'status' => 'active',
        ]);

        PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'ویلای مستقل',
            'description' => 'حیاط‌دار',
            'city' => 'تهران',
            'area' => 80,
            'bedrooms' => 3,
            'parking_count' => 0,
            'price' => 4000000000,
            'status' => 'active',
        ]);

        $this->get(route('properties.index', [
            'q' => 'نورگیر',
            'min_area' => 100,
            'min_price' => 7000000000,
            'bedrooms' => 2,
            'parking' => 1,
            'sort' => 'price_asc',
        ]))
            ->assertOk()
            ->assertSee($matching->title)
            ->assertDontSee('ویلای مستقل');
    }

    public function test_public_listing_detail_keeps_hidden_address_private(): void
    {
        $owner = $this->makeUser();
        $listing = PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'واحد با نشانی محرمانه',
            'description' => 'توضیحات آگهی',
            'city' => 'تهران',
            'address' => 'خیابان نمونه، پلاک ۱۲',
            'hide_address' => true,
            'status' => 'active',
        ]);

        $this->get(route('properties.show', $listing))
            ->assertOk()
            ->assertSee($listing->title)
            ->assertDontSee('خیابان نمونه، پلاک ۱۲');
    }

    private function makeUser(): User
    {
        return User::create([
            'mobile' => '09'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'first_name' => 'کاربر',
            'last_name' => 'آزمایشی',
        ]);
    }
}
