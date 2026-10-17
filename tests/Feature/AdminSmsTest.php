<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use App\Models\SmsLog;
use App\Models\SmsProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminSmsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $perms): AdminUser
    {
        static $n = 0; $n++;
        $u = AdminUser::create(['username' => "sms{$n}", 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => false]);
        $r = AdminRole::create(['name' => "smsrole{$n}"]);
        $r->syncPermissions($perms);
        $u->roles()->attach($r->id);

        return $u;
    }

    private function sup(): AdminUser
    {
        return $this->admin(['sms.settings', 'sms.logs']);
    }

    private function payload(array $o = []): array
    {
        return $o + [
            'name' => 'اپراتور تست', 'method' => 'POST', 'number_format' => 'local',
            'url' => 'https://api.example.com/send', 'body_type' => 'form',
            'body' => "to={{mobile}}\ntext={{message}}\nkey={{api_key}}",
            'params' => [['name' => 'api_key', 'value' => 'TOPSECRET', 'secret' => '1']],
            'timeout' => 10, 'is_active' => 1,
        ];
    }

    private function make(array $o = []): SmsProvider
    {
        $this->actingAs($this->sup(), 'admin')->post(route('admin.sms.providers.store'), $this->payload($o))->assertSessionHasNoErrors();

        return SmsProvider::latest('id')->first();
    }

    public function test_permissions_required(): void
    {
        $this->actingAs($this->admin(['plans.manage']), 'admin');
        $this->get(route('admin.sms.providers.index'))->assertForbidden();
        $this->get(route('admin.sms.logs.index'))->assertForbidden();
    }

    public function test_pages_render(): void
    {
        $this->actingAs($this->sup(), 'admin');
        $this->get(route('admin.sms.providers.index'))->assertOk();
        $this->get(route('admin.sms.providers.create'))->assertOk()->assertSee('کاوه');
        $this->get(route('admin.sms.logs.index'))->assertOk();
    }

    public function test_store_encrypts_and_edit_does_not_leak_secret(): void
    {
        $p = $this->make();
        $this->assertStringNotContainsString('TOPSECRET', (string) \DB::table('sms_providers')->value('config'));
        $this->get(route('admin.sms.providers.edit', $p->id))->assertOk()->assertDontSee('TOPSECRET');
    }

    public function test_blank_secret_keeps_old_value(): void
    {
        $p = $this->make();
        $pl = $this->payload(['name' => 'جدید', 'params' => [['name' => 'api_key', 'value' => '', 'secret' => '1']]]);
        $this->put(route('admin.sms.providers.update', $p->id), $pl)->assertSessionHasNoErrors();
        $p->refresh();
        $this->assertSame('TOPSECRET', $p->settings()['params'][0]['value']);
        $this->assertSame('جدید', $p->name);
    }

    public function test_validation(): void
    {
        $this->actingAs($this->sup(), 'admin');
        $this->post(route('admin.sms.providers.store'), $this->payload(['body' => 'x={{nope}}']))->assertSessionHasErrors();
        $this->post(route('admin.sms.providers.store'), $this->payload(['url' => 'ftp://x.com/a']))->assertSessionHasErrors('url');
        $this->post(route('admin.sms.providers.store'), $this->payload(['body_type' => 'json', 'body' => '{bad']))->assertSessionHasErrors();
        $this->post(route('admin.sms.providers.store'), $this->payload(['is_active' => 0, 'is_default' => 1]))->assertSessionHasErrors();
        $this->assertSame(0, SmsProvider::count());
    }

    public function test_default_switch_and_toggle(): void
    {
        $a = $this->make(['name' => 'A', 'is_default' => 1]);
        $b = $this->make(['name' => 'B']);
        $this->post(route('admin.sms.providers.default', $b->id));
        $this->assertFalse($a->fresh()->is_default);
        $this->assertTrue($b->fresh()->is_default);
        $this->post(route('admin.sms.providers.toggle', $b->id));
        $this->assertFalse($b->fresh()->is_active);
        $this->assertFalse($b->fresh()->is_default);
    }

    public function test_test_send_success_and_failure(): void
    {
        $p = $this->make();
        Http::fake(['api.example.com/*' => Http::sequence()->push('{"ok":1}', 200)->push('err', 500)]);
        $this->post(route('admin.sms.providers.test', $p->id), ['mobile' => '09123456789', 'message' => 'سلام'])
            ->assertSessionHas('sms_test', fn ($t) => $t['ok'] === true);
        $this->assertTrue($p->fresh()->last_test_ok);
        $this->assertDatabaseHas('sms_logs', ['template_key' => 'admin_test']);

        $this->post(route('admin.sms.providers.test', $p->id), ['mobile' => '09123456789'])
            ->assertSessionHas('sms_test', fn ($t) => $t['ok'] === false);
        $this->assertFalse($p->fresh()->last_test_ok);
    }

    public function test_logs_filter_and_csv_sanitized(): void
    {
        $this->actingAs($this->sup(), 'admin');
        SmsLog::create(['mobile' => '09120000001', 'body' => '=HYPERLINK("x")', 'status' => 'sent', 'template_key' => 'otp']);
        SmsLog::create(['mobile' => '09120000002', 'body' => 'b', 'status' => 'failed', 'template_key' => 'otp', 'error' => 'boom']);
        $this->get(route('admin.sms.logs.index', ['status' => 'failed']))->assertOk()->assertSee('09120000002')->assertDontSee('09120000001');
        $csv = $this->get(route('admin.sms.logs.export'))->assertOk()->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }
}
