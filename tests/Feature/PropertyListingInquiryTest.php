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

    public function test_users_can_start_a_private_conversation_and_exchange_messages(): void
    {
        $owner = $this->makeUser();
        $sender = $this->makeUser();
        $listing = $this->makeListing($owner);

        $this->actingAs($sender)->post(route('properties.inquiries.store', $listing), [
            'message' => 'برای بازدید ملک چه زمانی امکان هماهنگی دارید؟',
            'share_mobile' => '1',
        ])->assertRedirect();

        $inquiry = PropertyListingInquiry::query()->where('sender_user_id', $sender->id)->firstOrFail();

        $this->assertDatabaseHas('property_listing_inquiries', [
            'id' => $inquiry->id,
            'recipient_user_id' => $owner->id,
            'sender_mobile' => $sender->mobile,
            'status' => 'new',
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $owner->id,
            'type' => PropertyListingNotification::class,
        ]);

        $this->actingAs($sender)->get(route('properties.chat.show', $inquiry))
            ->assertOk()->assertSee($inquiry->message);

        $this->actingAs($sender)->post(route('properties.chat.send', $inquiry), [
            'body' => 'ممنون، چه ساعتی برای بازدید مناسب است؟',
        ])->assertRedirect(route('properties.chat.show', $inquiry));

        $this->assertDatabaseHas('property_listing_chat_messages', [
            'inquiry_id' => $inquiry->id,
            'sender_user_id' => $sender->id,
            'body' => 'ممنون، چه ساعتی برای بازدید مناسب است؟',
        ]);

        $this->actingAs($owner)->get(route('properties.chat.show', $inquiry))
            ->assertOk()->assertSee('چه ساعتی برای بازدید مناسب است؟');
    }

    public function test_only_conversation_participants_can_read_or_send_messages(): void
    {
        $owner = $this->makeUser();
        $sender = $this->makeUser();
        $stranger = $this->makeUser();
        $listing = $this->makeListing($owner);
        $inquiry = PropertyListingInquiry::create([
            'property_listing_id' => $listing->id,
            'sender_user_id' => $sender->id,
            'recipient_user_id' => $owner->id,
            'sender_name' => $sender->display_name,
            'message' => 'درخواست بازدید ملک دارم.',
            'status' => 'new',
        ]);

        $this->actingAs($stranger)->get(route('properties.chat.show', $inquiry))->assertForbidden();
        $this->actingAs($stranger)->post(route('properties.chat.send', $inquiry), [
            'body' => 'پیام غیرمجاز',
        ])->assertForbidden();

        $this->actingAs($owner)->get(route('properties.inquiries.index'))
            ->assertOk()->assertSee($sender->display_name)->assertSee($listing->title);
    }

    public function test_listing_phone_is_hidden_by_default_and_can_be_opted_in(): void
    {
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner);

        $this->get(route('properties.show', $listing))->assertOk()->assertDontSee('tel:'.$owner->mobile);

        $listing->update(['show_phone' => true]);
        $this->get(route('properties.show', $listing))->assertOk()
            ->assertSee('tel:'.$owner->mobile)
            ->assertSee('sms:'.$owner->mobile);
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
