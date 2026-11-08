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

    public function test_seller_contact_modal_shows_call_and_sms_independently_and_keeps_chat_available(): void
    {
        $owner = $this->makeUser();
        $visitor = $this->makeUser();
        $listing = $this->makeListing($owner);

        $this->actingAs($visitor)->get(route('properties.show', $listing))
            ->assertOk()
            ->assertSee('ارتباط با فروشنده')
            ->assertSee('چت')
            ->assertSee('تماس تلفنی توسط فروشنده غیرفعال شده است')
            ->assertSee('ارسال پیامک توسط فروشنده غیرفعال شده است')
            ->assertDontSee('tel:'.$owner->mobile)
            ->assertDontSee('sms:'.$owner->mobile);

        $listing->update(['allow_phone_call' => true]);
        $this->actingAs($visitor)->get(route('properties.show', $listing))
            ->assertOk()
            ->assertSee('tel:'.$owner->mobile)
            ->assertSee($owner->mobile)
            ->assertDontSee('sms:'.$owner->mobile);

        $listing->update(['allow_sms' => true]);
        $this->actingAs($visitor)->get(route('properties.show', $listing))
            ->assertOk()
            ->assertSee('tel:'.$owner->mobile)
            ->assertSee('sms:'.$owner->mobile);
    }

    public function test_guests_can_use_enabled_phone_and_sms_options_but_must_sign_in_for_chat(): void
    {
        $owner = $this->makeUser();
        $listing = $this->makeListing($owner);
        $listing->update(['allow_phone_call' => true, 'allow_sms' => true]);

        $this->get(route('properties.show', $listing))
            ->assertOk()
            ->assertSee('ارتباط با فروشنده')
            ->assertSee('tel:'.$owner->mobile)
            ->assertSee('sms:'.$owner->mobile)
            ->assertSee(route('login'))
            ->assertSee('برای شروع گفتگو وارد حساب کاربری شوید');
    }

    public function test_seller_can_configure_call_and_sms_permissions_on_listing_form(): void
    {
        $owner = $this->makeUser();
        $this->actingAs($owner)->get(route('properties.create'))
            ->assertOk()
            ->assertSee('name="allow_phone_call"', false)
            ->assertSee('name="allow_sms"', false);
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
