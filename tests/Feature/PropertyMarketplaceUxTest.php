<?php

namespace Tests\Feature;

use App\Models\PropertyListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PropertyMarketplaceUxTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'listing_type' => 'sale',
        'property_type' => 'residential',
        'title' => 'آگهی آزمایشی',
        'description' => 'توضیحات کافی برای آگهی آزمایشی',
        'city' => 'تهران',
        'price' => 5000000000,
    ];

    public function test_form_is_split_into_cards_with_required_marks_suffixes_and_uploader(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('properties.create'))
            ->assertOk()
            ->assertSee('اطلاعات اصلی')
            ->assertSee('مشخصات ملک')
            ->assertSee('توضیحات و تصاویر')
            ->assertSee('راه‌های ارتباطی')
            ->assertSee('class="req"', false)
            ->assertSee('data-money', false)
            ->assertSee('تومان')
            ->assertSee('متر')
            ->assertSee('inputmode="numeric"', false)
            ->assertSee('id="dropzone"', false)
            ->assertSee('۰ از ۱۲');
    }

    public function test_form_renders_only_price_fields_relevant_to_the_listing_type(): void
    {
        $user = $this->makeUser();
        $this->actingAs($user);

        $sale = $this->get(route('properties.create'))->getContent();
        $this->assertMatchesRegularExpression('/data-for="rent rent_request"\s+hidden/', $sale);
        $this->assertDoesNotMatchRegularExpression('/data-for="sale buy_request"\s+hidden/', $sale);

        $listing = PropertyListing::create(array_merge($this->payload, ['user_id' => $user->id, 'listing_type' => 'rent', 'price' => null, 'monthly_rent' => 9000000, 'status' => 'active']));
        $rent = $this->get(route('properties.edit', $listing))->getContent();
        $this->assertDoesNotMatchRegularExpression('/data-for="rent rent_request"\s+hidden/', $rent);
        $this->assertMatchesRegularExpression('/data-for="sale buy_request"\s+hidden/', $rent);
    }

    public function test_persian_digits_and_thousand_separators_are_accepted_for_numeric_fields(): void
    {
        $this->actingAs($this->makeUser())
            ->post(route('properties.store'), array_merge($this->payload, [
                'price' => '۵٬۰۰۰٬۰۰۰٬۰۰۰',
                'area' => '۸۵',
                'bedrooms' => '۲',
                'built_year' => '۱۴۰۲',
            ]))
            ->assertRedirect(route('properties.mine'));

        $this->assertDatabaseHas('property_listings', ['price' => 5000000000, 'bedrooms' => 2, 'built_year' => 1402]);
        $this->assertSame(85.0, (float) PropertyListing::firstOrFail()->area);
    }

    public function test_missing_price_is_reported_next_to_the_field_instead_of_an_error_page(): void
    {
        $this->actingAs($this->makeUser());

        $this->from(route('properties.create'))
            ->post(route('properties.store'), array_merge($this->payload, ['price' => '']))
            ->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors(['price' => 'برای آگهی فروش، قیمت را وارد کنید.']);

        $this->get(route('properties.create'))
            ->assertSee('id="f-price-err"', false)
            ->assertSee('برای آگهی فروش، قیمت را وارد کنید.')
            ->assertSee('id="form-alert"', false);
    }

    public function test_rent_requires_deposit_or_monthly_rent_with_field_errors(): void
    {
        $this->actingAs($this->makeUser())
            ->from(route('properties.create'))
            ->post(route('properties.store'), array_merge($this->payload, ['listing_type' => 'rent', 'price' => null]))
            ->assertRedirect(route('properties.create'))
            ->assertSessionHasErrors(['deposit', 'monthly_rent']);
    }

    public function test_required_field_errors_are_persian_and_per_field(): void
    {
        $this->actingAs($this->makeUser())
            ->from(route('properties.create'))
            ->post(route('properties.store'), array_merge($this->payload, ['city' => '', 'title' => '']))
            ->assertSessionHasErrors(['city' => 'این فیلد را پر کنید.', 'title' => 'این فیلد را پر کنید.']);
    }

    public function test_price_fields_not_relevant_to_the_listing_type_are_not_stored(): void
    {
        $this->actingAs($this->makeUser());

        $this->post(route('properties.store'), array_merge($this->payload, ['listing_type' => 'sale', 'deposit' => 100, 'monthly_rent' => 5]))->assertRedirect();
        $this->post(route('properties.store'), array_merge($this->payload, ['title' => 'اجاره', 'listing_type' => 'rent', 'price' => 999, 'deposit' => 200000000, 'monthly_rent' => 8000000]))->assertRedirect();

        $sale = PropertyListing::where('listing_type', 'sale')->firstOrFail();
        $this->assertNull($sale->deposit);
        $this->assertNull($sale->monthly_rent);

        $rent = PropertyListing::where('listing_type', 'rent')->firstOrFail();
        $this->assertNull($rent->price);
        $this->assertSame(200000000, (int) $rent->deposit);
    }

    public function test_chosen_cover_photo_becomes_the_first_photo_on_update(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();
        $listing = PropertyListing::create(array_merge($this->payload, ['user_id' => $user->id, 'status' => 'active', 'photos' => ['a.jpg', 'b.jpg', 'c.jpg']]));

        $this->actingAs($user)
            ->put(route('properties.update', $listing), array_merge($this->payload, [
                'existing_photos' => ['a.jpg', 'b.jpg', 'c.jpg'],
                'cover_photo' => 'existing:c.jpg',
            ]))
            ->assertRedirect(route('properties.mine'));

        $this->assertSame(['c.jpg', 'a.jpg', 'b.jpg'], $listing->fresh()->photos);
    }

    public function test_more_than_twelve_photos_on_update_is_a_field_error(): void
    {
        Storage::fake('public');
        $user = $this->makeUser();
        $photos = array_map(fn ($i) => "p$i.jpg", range(1, 12));
        $listing = PropertyListing::create(array_merge($this->payload, ['user_id' => $user->id, 'status' => 'active', 'photos' => $photos]));

        $this->actingAs($user)
            ->from(route('properties.edit', $listing))
            ->put(route('properties.update', $listing), array_merge($this->payload, [
                'existing_photos' => $photos,
                'photos' => [\Illuminate\Http\UploadedFile::fake()->image('x.jpg')],
            ]))
            ->assertSessionHasErrors('photos');
    }

    public function test_listing_views_are_counted_for_visitors_but_not_for_the_owner(): void
    {
        $owner = $this->makeUser();
        $listing = PropertyListing::create(array_merge($this->payload, ['user_id' => $owner->id, 'status' => 'active']));

        $this->get(route('properties.show', $listing))->assertOk();
        $this->get(route('properties.show', $listing))->assertOk();
        $this->actingAs($owner)->get(route('properties.show', $listing))->assertOk();

        $this->assertSame(2, (int) $listing->fresh()->views_count);
    }

    public function test_my_listings_cards_show_status_views_primary_edit_and_overflow_menu_with_delete_confirmation(): void
    {
        $owner = $this->makeUser();
        $listing = PropertyListing::create(array_merge($this->payload, ['user_id' => $owner->id, 'status' => 'active']));
        $listing->forceFill(['views_count' => 12])->save();

        $this->actingAs($owner)->get(route('properties.mine'))
            ->assertOk()
            ->assertSee('class="btn" href="'.route('properties.edit', $listing).'"', false)
            ->assertSee('class="more-menu"', false)
            ->assertSee('غیرفعال‌سازی')
            ->assertSee('data-delete-open', false)
            ->assertSee('id="delete-dialog"', false)
            ->assertSee('۱۲ بازدید')
            ->assertSee('فعال');
    }

    public function test_my_listings_packages_section_is_collapsible_and_missing_gateway_is_a_calm_notice(): void
    {
        $this->actingAs($this->makeUser())->get(route('properties.mine'))
            ->assertOk()
            ->assertSee('class="panel collapsible"', false)
            ->assertSee('class="info-bar"', false)
            ->assertDontSee('درگاه پرداخت فعالی برای خرید بسته تنظیم نشده است.');
    }

    public function test_empty_states_explain_what_to_do_next(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)->get(route('properties.mine'))
            ->assertSee('هنوز آگهی ثبت نکرده‌اید')
            ->assertSee('ثبت اولین آگهی')
            ->assertSee(route('properties.create'), false);

        $this->get(route('properties.index'))
            ->assertSee('هنوز آگهی منتشرشده‌ای نداریم')
            ->assertSee('ثبت آگهی رایگان');

        $this->get(route('properties.index', ['city' => 'شهری-که-وجود-ندارد']))
            ->assertSee('آگهی‌ای با این مشخصات پیدا نشد')
            ->assertSee('پاک‌کردن فیلترها');

        $this->get(route('properties.inquiries.index'))
            ->assertSee('هنوز گفتگویی ندارید')
            ->assertSee(route('properties.create'), false);
    }

    public function test_app_layout_links_to_the_marketplace_and_completed_profile_is_titled_my_profile(): void
    {
        $user = User::create([
            'mobile' => '09120000777',
            'first_name' => 'کاربر',
            'last_name' => 'کامل',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('<title>پروفایل من', false)
            ->assertSee('aria-label="بازار املاک"', false)
            ->assertSee(route('properties.index'), false);
    }

    public function test_marketplace_stylesheets_never_use_text_smaller_than_twelve_pixels(): void
    {
        foreach (['css/marketplace.css', 'css/chat.css'] as $file) {
            $css = file_get_contents(public_path($file));
            preg_match_all('/font-size:\s*([0-9.]+)px/', $css, $m);
            foreach ($m[1] as $size) {
                $this->assertGreaterThanOrEqual(12.0, (float) $size, "$file contains font-size:{$size}px");
            }
        }
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
