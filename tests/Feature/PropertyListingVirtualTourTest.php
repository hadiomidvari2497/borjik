<?php

namespace Tests\Feature;

use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** @group virtual-tour */
class PropertyListingVirtualTourTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_a_360_room_and_it_appears_in_public_tour(): void
    {
        Storage::fake('public');
        $owner = $this->makeUser('09120000111');
        $listing = $this->makeListing($owner);

        $this->actingAs($owner)->post(route('properties.tour-rooms.store', $listing), [
            'name' => 'پذیرایی',
            'panorama' => UploadedFile::fake()->image('living-room.jpg', 2048, 1024),
        ])->assertRedirect()->assertSessionHasNoErrors();

        $room = $listing->tourRooms()->firstOrFail();
        Storage::disk('public')->assertExists($room->image_path);

        $this->get(route('properties.show', $listing))
            ->assertOk()
            ->assertSee('شروع خانه‌گردی ۳۶۰ درجه')
            ->assertSee('پذیرایی')
            ->assertSee('data-tour-rooms', false);
    }

    public function test_another_user_cannot_add_or_remove_tour_rooms(): void
    {
        Storage::fake('public');
        $owner = $this->makeUser('09120000112');
        $other = $this->makeUser('09120000113');
        $listing = $this->makeListing($owner);
        $room = $listing->tourRooms()->create(['name' => 'آشپزخانه', 'image_path' => 'property-tours/1/kitchen.jpg']);

        $this->actingAs($other)->post(route('properties.tour-rooms.store', $listing), [
            'name' => 'اتاق خواب',
            'panorama' => UploadedFile::fake()->image('bedroom.jpg'),
        ])->assertForbidden();

        auth()->logout();
        $this->delete(route('properties.tour-rooms.destroy', [$listing, $room]))->assertRedirect(route('login'));
        $this->actingAs($other)->delete(route('properties.tour-rooms.destroy', [$listing, $room]))->assertForbidden();
        $this->assertDatabaseHas('property_listing_tour_rooms', ['id' => $room->id]);
        $this->actingAs($owner)->get(route('properties.edit', $listing))->assertOk()->assertSee('خانه‌گردی مجازی ۳۶۰ درجه');
    }

    public function test_tour_room_upload_requires_a_valid_image_and_limits_rooms_to_eight(): void
    {
        Storage::fake('public');
        $owner = $this->makeUser('09120000114');
        $listing = $this->makeListing($owner);

        $this->actingAs($owner)->from(route('properties.edit', $listing))
            ->post(route('properties.tour-rooms.store', $listing), ['name' => 'پذیرایی'])
            ->assertSessionHasErrors(['panorama']);

        foreach (range(1, 8) as $i) {
            $listing->tourRooms()->create(['name' => 'فضای '.$i, 'image_path' => 'property-tours/'.$listing->id.'/'.$i.'.jpg', 'sort_order' => $i]);
        }

        $this->actingAs($owner)->post(route('properties.tour-rooms.store', $listing), [
            'name' => 'فضای نهم',
            'panorama' => UploadedFile::fake()->image('room.jpg'),
        ])->assertStatus(422);
    }

    private function makeUser(string $mobile): User
    {
        return User::create(['mobile' => $mobile, 'first_name' => 'مالک', 'last_name' => 'آزمایشی', 'status' => 'active', 'profile_completed_at' => now()]);
    }

    private function makeListing(User $owner): PropertyListing
    {
        return PropertyListing::create([
            'user_id' => $owner->id, 'listing_type' => 'sale', 'property_type' => 'residential',
            'title' => 'آپارتمان آزمایشی', 'description' => 'توضیحات کافی', 'city' => 'تهران',
            'price' => 5000000000, 'status' => 'active',
        ]);
    }
}
