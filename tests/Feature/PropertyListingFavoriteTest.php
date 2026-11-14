<?php

namespace Tests\Feature;

use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyListingFavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_and_remove_listing(): void
    {
        $user = $this->makeUser();
        $listing = $this->makeListing($this->makeUser());

        $this->actingAs($user)
            ->post(route('properties.favorites.toggle', $listing))
            ->assertRedirect();

        $this->assertDatabaseHas('property_listing_favorites', [
            'user_id' => $user->id,
            'property_listing_id' => $listing->id,
        ]);

        $this->actingAs($user)
            ->post(route('properties.favorites.toggle', $listing))
            ->assertRedirect();

        $this->assertDatabaseMissing('property_listing_favorites', [
            'user_id' => $user->id,
            'property_listing_id' => $listing->id,
        ]);
    }

    public function test_saved_list_is_private_and_only_shows_published_listings(): void
    {
        $user = $this->makeUser();
        $active = $this->makeListing($this->makeUser());
        $pending = $this->makeListing($this->makeUser(), 'pending');

        $user->favoritePropertyListings()->attach([$active->id, $pending->id]);

        $this->actingAs($user)
            ->get(route('properties.favorites.index'))
            ->assertOk()
            ->assertSee($active->title)
            ->assertDontSee($pending->title);

        $this->actingAs($this->makeUser())
            ->get(route('properties.favorites.index'))
            ->assertOk()
            ->assertDontSee($active->title);
    }

    public function test_guest_cannot_save_listing(): void
    {
        $listing = $this->makeListing($this->makeUser());

        $this->post(route('properties.favorites.toggle', $listing))
            ->assertRedirect(route('login'));
    }

    private function makeUser(): User
    {
        return User::create([
            'mobile' => '09'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'first_name' => 'کاربر',
            'last_name' => 'آزمایشی',
        ]);
    }

    private function makeListing(User $user, string $status = 'active'): PropertyListing
    {
        return PropertyListing::create([
            'user_id' => $user->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آگهی '.uniqid(),
            'description' => 'توضیحات',
            'city' => 'تهران',
            'status' => $status,
        ]);
    }
}
