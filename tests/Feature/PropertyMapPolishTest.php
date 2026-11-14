<?php

namespace Tests\Feature;

use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyMapPolishTest extends TestCase
{
    use RefreshDatabase;

    private const SDK = 'neshan-maplibre-sdk.umd.js';

    public function test_results_map_loads_shared_helper_after_sdk_and_explains_markers(): void
    {
        config(['services.neshan.map_key' => 'test-map-key']);

        $html = $this->get(route('properties.index'))->assertOk()
            ->assertSee('id="property-results-map"', false)
            ->assertSee('class="map-legend"', false)
            ->assertSee('موقعیت تقریبی')
            ->assertSee('property-listing-selected', false)
            ->assertSee('cluster: true', false)
            ->getContent();

        $this->assertLessThan(strpos($html, 'js/market-map.js'), strpos($html, self::SDK), 'market-map.js must load after the SDK');
        // استایل‌ها باید در marketplace.css باشند، نه داخل صفحه
        $this->assertStringNotContainsString('<style>'."\n".'.property-map-feature-card', $html);
    }

    public function test_results_map_is_not_loaded_without_a_map_key(): void
    {
        config(['services.neshan.map_key' => null]);

        $this->get(route('properties.index'))->assertOk()
            ->assertSee('نقشه هنوز فعال نشده')
            ->assertDontSee('js/market-map.js', false)
            ->assertDontSee(self::SDK, false);
    }

    public function test_detail_map_shows_route_links_only_for_exact_locations(): void
    {
        config(['services.neshan.map_key' => 'test-map-key']);
        $owner = $this->makeUser();

        $exact = $this->listing($owner, ['location_visibility' => 'exact']);
        $this->get(route('properties.show', $exact))->assertOk()
            ->assertSee('class="route-links"', false)
            ->assertSee('destination=35.6892,51.389', false)
            ->assertSee('js/market-map.js', false)
            ->assertSee('id="property-detail-map-fallback"', false);

        $approx = $this->listing($owner, ['title' => 'تقریبی', 'location_visibility' => 'approximate']);
        $html = $this->get(route('properties.show', $approx))->assertOk()->getContent();
        $this->assertStringNotContainsString('class="route-links"', $html);
        $this->assertStringNotContainsString('destination=', $html);
        // موقعیت واقعی ملک در صفحه‌ی عمومی لو نمی‌رود
        $this->assertStringNotContainsString('35.6892', $html);
    }

    public function test_location_picker_comes_before_the_submit_buttons_and_loads_the_helper(): void
    {
        config(['services.neshan.map_key' => 'test-map-key']);

        $html = $this->actingAs($this->makeUser())->get(route('properties.create'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'class="form-actions"'), strpos($html, 'property-location-card'));
        $this->assertStringContainsString('js/market-map.js', $html);
        $this->assertStringContainsString('property-selected-area', $html);
    }

    public function test_location_picker_without_key_still_precedes_submit_buttons(): void
    {
        config(['services.neshan.map_key' => null]);

        $html = $this->actingAs($this->makeUser())->get(route('properties.create'))->assertOk()->getContent();

        $this->assertLessThan(strpos($html, 'class="form-actions"'), strpos($html, 'property-location-card'));
        $this->assertStringContainsString('نقشه هنوز فعال نشده', $html);
    }

    public function test_map_helper_and_styles_follow_marketplace_rules(): void
    {
        $helper = file_get_contents(public_path('js/market-map.js'));
        $this->assertDoesNotMatchRegularExpression('/(?<![\w.$])(?:alert|confirm|prompt)\s*\(/', $helper);

        $css = file_get_contents(public_path('css/marketplace.css'));
        $this->assertStringContainsString('.property-map-feature-card{', $css);
        $this->assertStringContainsString('@media(pointer:coarse){.maplibregl-ctrl-group button{width:44px;height:44px}}', $css);

        foreach (['properties/index.blade.php', 'properties/show.blade.php', 'properties/form.blade.php'] as $view) {
            preg_match_all("/'text-size':\s*(\d+)/", file_get_contents(resource_path('views/'.$view)), $m);
            foreach ($m[1] as $size) {
                $this->assertGreaterThanOrEqual(12, (int) $size, "$view has map text smaller than 12px");
            }
        }
    }

    public function test_map_search_still_returns_blurred_points_for_approximate_listings(): void
    {
        $owner = $this->makeUser();
        $listing = $this->listing($owner, ['location_visibility' => 'approximate']);

        $point = $this->getJson(route('properties.map-search'))->assertOk()->json('data.0');

        $this->assertSame($listing->id, $point['id']);
        $this->assertSame('approximate', $point['location_visibility']);
        $this->assertNotEquals(35.6892, $point['latitude']);
    }

    private function listing(User $owner, array $attributes = []): PropertyListing
    {
        return PropertyListing::create(array_merge([
            'user_id' => $owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آگهی دارای موقعیت',
            'description' => 'توضیحات آگهی',
            'city' => 'تهران',
            'latitude' => 35.6892,
            'longitude' => 51.3890,
            'price' => 5000000000,
            'status' => 'active',
        ], $attributes));
    }

    private function makeUser(): User
    {
        return User::create([
            'mobile' => '09'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'first_name' => 'کاربر',
            'last_name' => 'نقشه',
        ]);
    }
}
