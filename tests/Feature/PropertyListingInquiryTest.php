<?php

namespace Tests\Feature;

use App\Models\PropertyListing;
use App\Models\PropertyListingInquiry;
use App\Models\User;
use App\Notifications\PropertyListingNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyListingInquiryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_contact_listing_owner_and_mobile_is_shared_only_with_explicit_consent(): void
    {
        $owner = $this->makeUser();
        $sender = $this->makeUser();
        $listing = $this->makeListing($owner);

        $this->actingAs($sender)->post(route('properties.inquiries.store', $listing), [
            'message' => 'برای بازدید ملک چه زمانی امکان هماهنگی دارید؟',
        ])->assertRedirect(route('properties.show', $listing));

        $this->assertDatabaseHas('property_listing_inquiries', [
            'property_listing_id' => $listing->id,
            'sender_user_id' => $sender->id,
            'recipient_user_id' => $owner->id,
            'sender_mobile' => null,
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'type' => PropertyListingNotification::class,
        ]);

        $this->actingAs($sender)->post(route('properties.inquiries.store', $listing), [
            'message' => 'برای بازدید ملک لطفاً با من هماهنگ کنید.',
            'share_mobile' => '1',
        ])->assertRedirect(route('properties.show', $listing));

        $this->assertDatabaseHas('property_listing_inquiries', [
            'property_listing_id' => $listing->id,
            'sender_user_id' => $sender->id,
            'sender_mobile' => $sender->mobile,
        ]);
    }

    public function test_owner_cannot_contact_own_listing_and_only_recipient_can_manage_inquiries(): void
    {
        $owner = $this->makeUser();
        $sender = $this->makeUser();
        $stranger = $this->makeUser();
        $listing = $this->makeListing($owner);

        $this->actingAs($owner)->post(route('properties.inquiries.store', $listing), [
            'message' => 'می‌خواهم درباره این ملک اطلاعات بگیرم.',
        ])->assertForbidden();

        $inquiry = PropertyListingInquiry::create([
            'property_listing_id' => $listing->id,
            'sender_user_id' => $sender->id,
            'recipient_user_id' => $owner->id,
            'sender_name' => $sender->display_name,
            'message' => 'درخواست بازدید ملک دارم.',
            'status' => 'new',
        ]);

        $this->actingAs($stranger)->patch(route('properties.inquiries.update', $inquiry), [
            'status' => 'contacted',
        ])->assertForbidden();

        $this->actingAs($owner)->get(route('properties.inquiries.index'))
            ->assertOk()
            ->assertSee($sender->display_name)
            ->assertSee($listing->title);

        $this->actingAs($owner)->patch(route('properties.inquiries.update', $inquiry), [
            'status' => 'contacted',
        ])->assertRedirect();

        $this->assertDatabaseHas('property_listing_inquiries', [
            'id' => $inquiry->id,
            'status' => 'contacted',
        ]);
    }

    private function makeListing(User $owner): PropertyListing
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

    private function makeUser(): User
    {
        return User::create([
            'mobile' => '09'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT),
            'first_name' => 'کاربر',
            'last_name' => 'آزمایشی',
        ]);
    }
}
