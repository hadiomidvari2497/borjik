<?php

namespace Tests\\Feature;

use App\\Models\\PropertyListing;
use App\\Models\\PropertyListingCreditTransaction;
use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Tests\\TestCase;

class PropertyListingRenewalExpirationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(): User
    {
        return User::create([
            'first_name' => 'کاربر',
            'last_name' => 'آزمایشی',
            'mobile' => '09129990001',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
    }

    private function makeListing(User $user, array $overrides = []): PropertyListing
    {
        return PropertyListing::create(array_merge([
            'user_id' => $user->id,
            'listing_type' => 'sale',
            'property_type' => 'residential',
            'title' => 'آگهی آزمایشی',
            'description' => 'توضیحات آگهی',
            'city' => 'تهران',
            'price' => 500000000,
            'status' => 'active',
            'expires_at' => now()->subDay(),
        ], $overrides));
    }

    public function test_expiration_command_marks_listing_expired_and_notifies_owner(): void
    {
        $user = $this->makeUser();
        $listing = $this->makeListing($user);

        $this->artisan('properties:expire-listings')->assertSuccessful();

        $this->assertSame('expired', $listing->fresh()->status);
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => \\App\\Notifications\\PropertyListingNotification::class,
        ]);
        $this->get(route('properties.show', $listing))->assertNotFound();
    }

    public function test_expired_listing_can_be_renewed_with_one_credit(): void
    {
        $this->travelTo(now()->startOfDay());
        $user = $this->makeUser();
        $listing = $this->makeListing($user, ['status' => 'expired']);

        PropertyListingCreditTransaction::create([
            'user_id' => $user->id,
            'quantity' => 1,
            'type' => 'purchase',
            'description' => 'اعتبار آزمایشی',
        ]);

        $this->actingAs($user)
            ->post(route('properties.renew', $listing))
            ->assertRedirect(route('properties.mine'));

        $listing->refresh();
        $this->assertSame('active', $listing->status);
        $this->assertEquals(now()->addDays(30)->timestamp, $listing->expires_at->timestamp);
        $this->assertSame(0, app(\\App\\Services\\PropertyListingQuota::class)->availableCredits($user->id));
        $this->assertDatabaseHas('property_listing_credit_transactions', [
            'user_id' => $user->id,
            'property_listing_id' => $listing->id,
            'quantity' => -1,
            'type' => 'renewal',
        ]);
        $this->get(route('properties.show', $listing))->assertOk();
    }

    public function test_renewal_without_credit_is_rejected_without_changing_listing(): void
    {
        $user = $this->makeUser();
        $listing = $this->makeListing($user, ['status' => 'expired']);

        $this->actingAs($user)
            ->post(route('properties.renew', $listing))
            ->assertForbidden();

        $this->assertSame('expired', $listing->fresh()->status);
        $this->assertDatabaseCount('property_listing_credit_transactions', 0);
    }
}
