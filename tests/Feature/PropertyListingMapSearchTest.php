<?php

namespace Tests\Feature;

use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyListingMapSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_search_returns_only_published_geolocated_listings_and_blurs_coordinates(): void
    {
        $owner = $this->makeUser();
        $visible = PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آپارتمان روی نقشه',
            'description' => 'توضیحات',
            'city' => 'تهران',
            'latitude' => 35.6892,
            'longitude' => 51.3890,
            'price' => 5000000000,
            'status' => 'active',
        ]);

        PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آگهی در انتظار تأیید',
            'description' => 'توضیحات',
            'city' => 'تهران',
            'latitude' => 35.6892,
            'longitude' => 51.3890,
            'status' => 'pending',
        ]);

        PropertyListing::create([
            'user_id' => $owner->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آگهی بدون موقعیت',
            'description' => 'توضیحات',
            'city' => 'تهران',
            'status' => 'active',
        ]);

        $response = $this->getJson(route('properties.map-search', [
            'south' => 35.60, 'west' => 51.30, 'north' => 35.80, 'east' => 51.50,
        ]));

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $visible->id);
        $point = $response->json('data.0');
        $this->assertNotSame('35.6892', (string) $point['latitude']);
        $this->assertNotSame('51.389', (string) $point['longitude']);
        $this->assertArrayNotHasKey('address', $point);
    }

    public function test_map_search_rejects_invalid_bounds(): void
    {
        $this->getJson(route('properties.map-search', [
            'south' => 36, 'west' => 52, 'north' => 35, 'east' => 51,
        ]))->assertStatus(422);
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
