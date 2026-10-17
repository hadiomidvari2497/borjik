<?php

namespace Tests\Feature;

use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** مدیریت کاربران، نقش‌ها و مجوزهای پنل مدیریت سیستم (بند ۴.۲۹ سند). */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(array $attrs = [], array $perms = []): AdminUser
    {
        static $n = 0;
        $n++;
        $user = AdminUser::create($attrs + [
            'username' => "adm{$n}", 'password' => 'secret-pass-1', 'status' => 'active', 'is_super' => false,
        ]);
        if ($perms) {
            $role = AdminRole::create(['name' => "role{$n}"]);
            $role->syncPermissions($perms);
            $user->roles()->attach($role->id);
        }

        return $user;
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin/users')->assertRedirect(route('admin.login'));
    }

    public function test_user_without_permission_gets_403_and_hidden_menu(): void
    {
        $a = $this->admin([], ['plans.manage']);

        $this->actingAs($a, 'admin')->get('/admin/plans')->assertOk();
        $this->actingAs($a, 'admin')->get('/admin/users')->assertForbidden();
        $this->actingAs($a, 'admin')->get('/admin/roles')->assertForbidden();
        $this->actingAs($a, 'admin')->get('/admin')->assertOk()
            ->assertDontSee(route('admin.users.index'), false);
    }

    public function test_super_admin_has_every_permission(): void
    {
        $s = $this->admin(['is_super' => true]);

        $this->actingAs($s, 'admin')->get('/admin/users')->assertOk();
        $this->actingAs($s, 'admin')->get('/admin/roles')->assertOk();
        $this->actingAs($s, 'admin')->get('/admin/plans')->assertOk();
    }

    public function test_login_records_last_login(): void
    {
        $a = $this->admin(['username' => 'boss', 'password' => 'secret-pass-1']);

        $this->post('/admin/login', ['username' => 'boss', 'password' => 'secret-pass-1'])->assertRedirect();
        $this->assertNotNull($a->fresh()->last_login_at);
    }

    public function test_disabled_admin_cannot_log_in(): void
    {
        $this->admin(['username' => 'off', 'status' => 'disabled']);

        $this->post('/admin/login', ['username' => 'off', 'password' => 'secret-pass-1'])->assertSessionHasErrors('username');
        $this->assertGuest('admin');
    }

    public function test_cannot_grant_permissions_one_does_not_have(): void
    {
        $a = $this->admin([], ['admin.roles.manage', 'plans.manage']);

        $this->actingAs($a, 'admin')->post('/admin/roles', [
            'name' => 'greedy', 'permissions' => ['plans.manage', 'logs.view'],
        ])->assertSessionHasErrors('permissions');
        $this->assertDatabaseMissing('admin_roles', ['name' => 'greedy']);

        $this->actingAs($a, 'admin')->post('/admin/roles', [
            'name' => 'ok', 'permissions' => ['plans.manage'],
        ])->assertRedirect(route('admin.roles.index'));
        $this->assertSame(['plans.manage'], AdminRole::where('name', 'ok')->first()->permissionKeys());
    }

    public function test_non_super_cannot_create_super_or_assign_stronger_role(): void
    {
        $a = $this->admin([], ['admin.users.manage']);
        $strong = AdminRole::create(['name' => 'strong']);
        $strong->syncPermissions(['logs.view']);

        $this->actingAs($a, 'admin')->post('/admin/users', [
            'username' => 'newbie', 'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1',
            'status' => 'active', 'is_super' => 1,
        ])->assertRedirect();
        $this->assertFalse(AdminUser::where('username', 'newbie')->first()->is_super);

        $this->actingAs($a, 'admin')->post('/admin/users', [
            'username' => 'newbie2', 'password' => 'long-enough-1', 'password_confirmation' => 'long-enough-1',
            'status' => 'active', 'roles' => [$strong->id],
        ])->assertSessionHasErrors('roles');
    }

    public function test_non_super_cannot_touch_super_admin(): void
    {
        $a = $this->admin([], ['admin.users.manage']);
        $s = $this->admin(['is_super' => true]);

        $this->actingAs($a, 'admin')->get("/admin/users/{$s->id}/edit")->assertForbidden();
        $this->actingAs($a, 'admin')->delete("/admin/users/{$s->id}")->assertForbidden();
    }

    public function test_last_active_super_admin_is_protected_and_self_delete_blocked(): void
    {
        $s = $this->admin(['is_super' => true]);

        $this->actingAs($s, 'admin')->delete("/admin/users/{$s->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('admin_users', ['id' => $s->id]);

        $other = $this->admin(['is_super' => true]);
        $this->actingAs($s, 'admin')->put("/admin/users/{$other->id}", [
            'username' => $other->username, 'status' => 'disabled', 'is_super' => 1,
        ])->assertRedirect();
        $this->assertSame('disabled', $other->fresh()->status);

        // اکنون s آخرین سوپرادمین فعال است؛ نمی‌تواند خودش را از سوپر بودن بیندازد
        $this->actingAs($s, 'admin')->put("/admin/users/{$s->id}", [
            'username' => $s->username, 'status' => 'active',
        ])->assertRedirect();
        $this->assertTrue($s->fresh()->is_super);
    }

    public function test_role_with_users_cannot_be_deleted(): void
    {
        $s = $this->admin(['is_super' => true]);
        $holder = $this->admin([], ['plans.manage']);
        $role = $holder->roles()->first();

        $this->actingAs($s, 'admin')->delete("/admin/roles/{$role->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('admin_roles', ['id' => $role->id]);
    }

    public function test_admin_login_ignores_a_stale_building_panel_url(): void
    {
        $this->admin(['username' => 'boss', 'password' => 'secret-pass-1']);

        // کاربر قبلاً صفحهٔ پنل ساختمان را بدون ورود باز کرده؛ آدرس آن در نشست می‌ماند
        $this->get('/')->assertRedirect(route('login'));
        $this->post('/admin/login', ['username' => 'boss', 'password' => 'secret-pass-1'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertNull(session('url.intended'));
    }

    public function test_admin_login_still_returns_to_the_admin_page_that_was_requested(): void
    {
        $this->admin(['username' => 'boss', 'password' => 'secret-pass-1', 'is_super' => true]);

        $this->get('/admin/plans')->assertRedirect(route('admin.login'));
        $this->post('/admin/login', ['username' => 'boss', 'password' => 'secret-pass-1'])
            ->assertRedirect(url('/admin/plans'));
    }

    public function test_admin_login_never_redirects_to_another_host(): void
    {
        $this->admin(['username' => 'boss', 'password' => 'secret-pass-1']);

        $this->withSession(['url.intended' => 'https://evil.example/admin/plans'])
            ->post('/admin/login', ['username' => 'boss', 'password' => 'secret-pass-1'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_login_page_uses_the_admin_layout(): void
    {
        $this->get('/admin/login')->assertOk()
            ->assertSee('پنل مدیریت سیستم')
            ->assertSee('class="field"', false)
            ->assertDontSee('برای مدیران و ساکنین');
    }
}
