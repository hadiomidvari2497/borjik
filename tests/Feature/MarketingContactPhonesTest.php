<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\SiteSetting;
use App\Support\ContactPhones;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** چند شمارهٔ تماس در بخش «تماس با ما» صفحهٔ اصلی (پنل مدیریت + نمایش عمومی). */
class MarketingContactPhonesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): AdminUser
    {
        $user = AdminUser::create(['username' => 'cms-admin', 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => false]);
        $role = AdminRole::create(['name' => 'cms-role']);
        $role->syncPermissions(['site.settings']);
        $user->roles()->attach($role->id);

        return $user;
    }

    /** payload کامل فرم، با مقدارهای پیش‌فرض خود صفحه، و بخش تماس دلخواه. */
    private function payload(AdminUser $admin, array $contact): array
    {
        $content = $this->actingAs($admin, 'admin')->get('/admin/marketing-content')->assertOk()->viewData('content');
        $payload = $content;
        unset($payload['contact']['phone']);
        $payload['contact'] = array_replace($payload['contact'], ['email' => 'info@example.ir'], $contact);

        return $payload;
    }

    public function test_admin_can_save_multiple_phones_and_they_show_on_home(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put('/admin/marketing-content', $this->payload($admin, [
            'phones' => [
                ['label' => 'پشتیبانی', 'number' => '021-12345678'],
                ['label' => 'فروش', 'number' => '۰۹۱۲ ۱۲۳ ۴۵۶۷'],
                ['label' => '', 'number' => '+98 21 8765 4321'],
            ],
        ]))->assertRedirect(route('admin.marketing-content.edit'))->assertSessionHasNoErrors();

        $saved = SiteSetting::get('marketing.content')['contact'];
        $this->assertCount(3, $saved['phones']);
        $this->assertArrayNotHasKey('phone', $saved);

        $this->get('/')->assertOk()
            ->assertSee('شماره‌های تماس')
            ->assertSee('پشتیبانی')
            ->assertSee('021-12345678')
            ->assertSee('href="tel:02112345678"', false)
            ->assertSee('href="tel:09121234567"', false)
            ->assertSee('href="tel:+982187654321"', false);
    }

    public function test_empty_rows_are_dropped_and_section_hides_without_phones(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put('/admin/marketing-content', $this->payload($admin, [
            'phones' => [['label' => 'بی‌شماره', 'number' => ''], ['label' => '', 'number' => '']],
        ]))->assertSessionHasNoErrors();

        $this->assertSame([], SiteSetting::get('marketing.content')['contact']['phones']);
        $this->get('/')->assertOk()->assertDontSee('بی‌شماره')->assertDontSee('contact-phones"', false);
    }

    public function test_invalid_or_too_many_phones_are_rejected(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put('/admin/marketing-content', $this->payload($admin, [
            'phones' => [['label' => 'x', 'number' => 'abc<script>']],
        ]))->assertSessionHasErrors('contact.phones.0.number');

        $tooMany = array_fill(0, ContactPhones::MAX + 1, ['label' => '', 'number' => '021-1111']);
        $this->actingAs($admin, 'admin')->put('/admin/marketing-content', $this->payload($admin, ['phones' => $tooMany]))
            ->assertSessionHasErrors('contact.phones');
    }

    public function test_legacy_single_phone_still_shows_on_home_and_in_admin_form(): void
    {
        $admin = $this->admin();
        SiteSetting::set('marketing.content', ['contact' => ['phone' => '021-5550000', 'email' => 'a@b.ir']]);

        $this->get('/')->assertOk()->assertSee('021-5550000')->assertSee('href="tel:0215550000"', false);

        $this->actingAs($admin, 'admin')->get('/admin/marketing-content')->assertOk()
            ->assertSee('value="021-5550000"', false);
    }

    public function test_helper_normalizes_and_limits_phones(): void
    {
        $phones = ContactPhones::from(['phones' => [
            ['label' => ' دفتر ', 'number' => ' ۰۲۱-۱۲۳ '],
            'not-an-array',
            ['label' => 'خالی', 'number' => '  '],
        ]]);

        $this->assertSame([['label' => 'دفتر', 'number' => '۰۲۱-۱۲۳', 'href' => '021123']], $phones);
        $this->assertSame([], ContactPhones::from(null));
        $this->assertCount(ContactPhones::MAX, ContactPhones::from(['phones' => array_fill(0, 10, ['number' => '1'])]));
    }
}
