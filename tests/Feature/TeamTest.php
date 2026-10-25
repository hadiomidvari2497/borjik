<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\InvitationService;
use App\Services\Sms\SmsGateway;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** مدیریت پرسنل و اعضا: فهرست، فعال و غیرفعال‌سازی، لغو دعوت، نقش‌ها و قواعد دسترسی. */
class TeamTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private User $owner;
    private User $manager;
    private User $guard;
    private User $tenant;
    private object $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->gateway = new class implements SmsGateway {
            public array $sent = [];

            public function send(string $mobile, string $text, ?string $templateKey = null, array $templateVars = []): string
            {
                $this->sent[] = [$mobile, $text];

                return 'fake';
            }
        };
        $this->app->instance(SmsGateway::class, $this->gateway);

        $this->owner = $this->user('09120000001', 'مالکساختمان');
        $this->building = Building::create(['name' => 'برج آسمان', 'code' => '11111', 'created_by' => $this->owner->id]);
        $this->manager = $this->user('09120000002', 'مدیرساختمان');
        $this->guard = $this->user('09120000003', 'نگهبانتست');
        $this->tenant = $this->user('09120000004', 'مستأجرتست');

        $this->join($this->owner, 'owner');
        $this->join($this->manager, 'manager');
        $this->join($this->guard, 'security');
        $this->join($this->tenant, 'tenant');
    }

    private function user(string $mobile, string $name): User
    {
        return User::create(['mobile' => $mobile, 'first_name' => $name, 'last_name' => 'ن', 'status' => 'active', 'profile_completed_at' => now()]);
    }

    private function join(User $u, string $role, string $status = 'active', ?Building $b = null): void
    {
        BuildingUserRole::create(['building_id' => ($b ?? $this->building)->id, 'user_id' => $u->id, 'role_id' => Role::where('key', $role)->value('id'), 'status' => $status]);
    }

    private function as(User $u)
    {
        return $this->actingAs($u)->withSession(['current_building_id' => $this->building->id]);
    }

    private function roleStatus(User $u, string $role): ?string
    {
        return DB::table('building_user_roles as b')->join('roles as r', 'r.id', '=', 'b.role_id')
            ->where('b.building_id', $this->building->id)->where('b.user_id', $u->id)->where('r.key', $role)->value('b.status');
    }

    private function invite(string $mobile, string $role, ?User $by = null): string
    {
        return app(CurrentBuilding::class)->actingAs($this->building->id, function () use ($mobile, $role, $by) {
            [, $token] = app(InvitationService::class)->invite($mobile, $role, $by ?? $this->owner);

            return $token;
        });
    }

    public function test_staff_and_members_pages_list_only_their_own_group(): void
    {
        $this->as($this->manager)->get(route('staff.index'))->assertOk()->assertSee('نگهبانتست')->assertDontSee('مستأجرتست');
        $this->as($this->manager)->get(route('members.index'))->assertOk()->assertSee('مستأجرتست')->assertDontSee('نگهبانتست');
    }

    public function test_people_of_other_buildings_are_never_listed(): void
    {
        $other = Building::create(['name' => 'برج دیگر', 'code' => '22222', 'created_by' => $this->owner->id]);
        $stranger = $this->user('09129999999', 'غریبهدیگر');
        $this->join($stranger, 'security', 'active', $other);

        $this->as($this->manager)->get(route('staff.index'))->assertOk()->assertDontSee('غریبهدیگر');
    }

    public function test_manager_can_deactivate_and_reactivate_staff_and_members(): void
    {
        $this->as($this->manager)->post(route('team.deactivate', $this->guard->id), ['scope' => 'staff'])->assertSessionHasNoErrors();
        $this->assertSame('inactive', $this->roleStatus($this->guard, 'security'));
        $this->as($this->manager)->get(route('staff.index'))->assertSee('غیرفعال');

        $this->as($this->manager)->post(route('team.activate', $this->guard->id), ['scope' => 'staff'])->assertSessionHasNoErrors();
        $this->assertSame('active', $this->roleStatus($this->guard, 'security'));

        $this->as($this->manager)->post(route('team.deactivate', $this->tenant->id), ['scope' => 'members'])->assertSessionHasNoErrors();
        $this->assertSame('inactive', $this->roleStatus($this->tenant, 'tenant'));
    }

    public function test_a_deactivated_person_loses_access_to_the_building(): void
    {
        $this->as($this->manager)->post(route('team.deactivate', $this->guard->id), ['scope' => 'staff']);

        // عضو فعالی ندارد؛ به صفحه‌ی خوش‌آمدگویی برمی‌گردد و ساختمان را نمی‌بیند
        $this->as($this->guard)->get(route('units.index'))->assertRedirect(route('dashboard'));
    }

    public function test_a_manager_cannot_touch_the_owner_or_another_manager_or_themselves(): void
    {
        $this->as($this->manager)->post(route('team.deactivate', $this->owner->id), ['scope' => 'staff'])->assertForbidden();
        $this->assertSame('active', $this->roleStatus($this->owner, 'owner'));

        $this->as($this->manager)->post(route('team.deactivate', $this->manager->id), ['scope' => 'staff'])->assertSessionHasErrors('general');
        $this->as($this->manager)->put(route('staff.roles.update', $this->guard->id), ['roles' => ['security', 'manager']])->assertForbidden();
        $this->assertNull($this->roleStatus($this->guard, 'manager'));
    }

    public function test_the_owner_can_manage_managers_but_not_themselves(): void
    {
        $this->as($this->owner)->post(route('team.deactivate', $this->manager->id), ['scope' => 'staff'])->assertSessionHasNoErrors();
        $this->assertSame('inactive', $this->roleStatus($this->manager, 'manager'));

        $this->as($this->owner)->post(route('team.deactivate', $this->owner->id), ['scope' => 'staff'])->assertSessionHasErrors('general');
        $this->as($this->owner)->put(route('staff.roles.update', $this->owner->id), ['roles' => []])->assertSessionHasErrors('general');
        $this->assertSame('active', $this->roleStatus($this->owner, 'owner'));
    }

    public function test_roles_can_be_added_and_removed(): void
    {
        $this->as($this->owner)->put(route('staff.roles.update', $this->guard->id), ['roles' => ['security', 'maintenance']])->assertSessionHasNoErrors();
        $this->assertSame('active', $this->roleStatus($this->guard, 'maintenance')); // عضو فعال، نقش تازه هم فعال

        $this->as($this->manager)->put(route('staff.roles.update', $this->guard->id), ['roles' => ['maintenance', 'cleaning']])->assertSessionHasNoErrors();
        $this->assertNull($this->roleStatus($this->guard, 'security'));
        $this->assertSame('active', $this->roleStatus($this->guard, 'cleaning'));

        $this->as($this->owner)->put(route('staff.roles.update', $this->guard->id), ['roles' => ['bogus']])->assertSessionHasErrors('general');
    }

    public function test_member_role_can_be_changed_without_touching_other_buildings_or_staff_roles(): void
    {
        $other = Building::create(['name' => 'ساختمان دوم', 'code' => '33333', 'created_by' => $this->owner->id]);
        $this->join($this->tenant, 'tenant', 'active', $other);

        $this->as($this->manager)->post(route('members.membership-role.update', $this->tenant->id), [
            'role' => 'unit_owner',
        ])->assertSessionHasNoErrors();

        $this->assertNull($this->roleStatus($this->tenant, 'tenant'));
        $this->assertSame('active', $this->roleStatus($this->tenant, 'unit_owner'));
        $otherOwnerRole = DB::table('building_user_roles as b')
            ->join('roles as r', 'r.id', '=', 'b.role_id')
            ->where('b.building_id', $other->id)->where('b.user_id', $this->tenant->id)
            ->where('r.key', 'tenant')->value('b.status');
        $this->assertSame('active', $otherOwnerRole);
    }

    public function test_member_role_change_rejects_self_and_non_member_roles(): void
    {
        $this->as($this->tenant)->post(route('members.membership-role.update', $this->tenant->id), [
            'role' => 'unit_owner',
        ])->assertForbidden();

        $this->as($this->manager)->post(route('members.membership-role.update', $this->tenant->id), [
            'role' => 'manager',
        ])->assertSessionHasErrors('role');

        $this->assertSame('active', $this->roleStatus($this->tenant, 'tenant'));
        $this->assertNull($this->roleStatus($this->tenant, 'unit_owner'));
    }

    public function test_only_pending_invitations_can_be_cancelled_and_they_are_really_removed(): void
    {
        $this->invite('09121111111', 'cleaning');
        $user = User::where('mobile', '09121111111')->firstOrFail();
        $this->assertSame('invited', $this->roleStatus($user, 'cleaning'));

        $this->as($this->manager)->delete(route('team.invitation.cancel', $user->id), ['scope' => 'staff'])->assertSessionHasNoErrors();
        $this->assertNull($this->roleStatus($user, 'cleaning'));
        $this->assertSame(0, app(CurrentBuilding::class)->withoutScope(fn () => Invitation::count()));

        $this->as($this->manager)->delete(route('team.invitation.cancel', $this->guard->id), ['scope' => 'staff'])->assertSessionHasErrors('general'); // فعال است، دعوتی ندارد
        $this->assertSame('active', $this->roleStatus($this->guard, 'security'));
    }

    public function test_resending_an_invitation_creates_a_new_link_and_sms(): void
    {
        $this->invite('09121111111', 'cleaning');
        $this->as($this->manager)->post(route('members.invite'), ['mobile' => '09121111111', 'role' => 'cleaning'])->assertSessionHasNoErrors();

        $this->assertSame(2, app(CurrentBuilding::class)->withoutScope(fn () => Invitation::count()));
        $this->assertCount(2, $this->gateway->sent);
    }

    public function test_accepting_an_invitation_activates_every_pending_role(): void
    {
        $token = $this->invite('09121111111', 'security');
        $user = User::where('mobile', '09121111111')->firstOrFail();

        $this->as($this->owner)->put(route('staff.roles.update', $user->id), ['roles' => ['security', 'maintenance']])->assertSessionHasNoErrors();
        $this->assertSame('invited', $this->roleStatus($user, 'maintenance')); // هنوز دعوت را نپذیرفته

        app(InvitationService::class)->accept($token, $user);

        $this->assertSame('active', $this->roleStatus($user, 'security'));
        $this->assertSame('active', $this->roleStatus($user, 'maintenance'));
    }

    public function test_only_the_owner_may_invite_managers(): void
    {
        $this->as($this->manager)->post(route('members.invite'), ['mobile' => '09121234567', 'role' => 'manager'])->assertForbidden();
        $this->as($this->owner)->post(route('members.invite'), ['mobile' => '09121234567', 'role' => 'manager'])->assertSessionHasNoErrors();
        $this->assertSame('invited', $this->roleStatus(User::where('mobile', '09121234567')->firstOrFail(), 'manager'));
    }

    public function test_tenants_and_guards_cannot_open_the_management_pages(): void
    {
        foreach ([$this->tenant, $this->guard] as $user) {
            $this->as($user)->get(route('staff.index'))->assertForbidden();
            $this->as($user)->get(route('members.index'))->assertForbidden();
            $this->as($user)->post(route('team.deactivate', $this->manager->id), ['scope' => 'staff'])->assertForbidden();
        }
    }
}
