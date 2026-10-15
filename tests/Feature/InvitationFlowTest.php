<?php

namespace Tests\Feature;

use App\Exceptions\InvitationException;
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
use Tests\TestCase;

class InvitationFlowTest extends TestCase
{
    use RefreshDatabase;

    private object $gateway;
    private User $owner;
    private Building $building;
    private InvitationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->gateway = new class implements SmsGateway {
            public array $sent = [];

            public function send(string $mobile, string $text): string
            {
                $this->sent[] = [$mobile, $text];

                return 'fake';
            }
        };
        $this->app->instance(SmsGateway::class, $this->gateway);

        $this->owner = User::create(['mobile' => '09120000001', 'first_name' => 'مالک', 'last_name' => 'تست', 'status' => 'active']);
        $this->building = Building::create(['name' => 'برج یک', 'code' => '11111', 'created_by' => $this->owner->id]);
        BuildingUserRole::create([
            'building_id' => $this->building->id,
            'user_id' => $this->owner->id,
            'role_id' => Role::where('key', 'owner')->value('id'),
            'status' => 'active',
        ]);
        $this->service = app(InvitationService::class);
    }

    private function invite(string $mobile = '09121112233', string $role = 'tenant'): string
    {
        return app(CurrentBuilding::class)->actingAs($this->building->id, function () use ($mobile, $role) {
            [, $token] = $this->service->invite($mobile, $role, $this->owner);

            return $token;
        });
    }

    public function test_invite_creates_invited_user_membership_and_sms(): void
    {
        // APP_URL may be stale on XAMPP; invite URLs must point to the app that received the request.
        config(['app.url' => 'https://wrong.example']);
        $token = $this->invite();

        $user = User::where('mobile', '09121112233')->firstOrFail();
        $this->assertSame('invited', $user->status);
        $this->assertSame('invited', BuildingUserRole::where('user_id', $user->id)->firstOrFail()->status->value);
        $this->assertStringContainsString($token, $this->gateway->sent[0][1]);
        $this->assertStringContainsString('/invite/'.$token, $this->gateway->sent[0][1]);
        $this->assertStringNotContainsString('wrong.example', $this->gateway->sent[0][1]);
        $this->assertStringContainsString('برج یک', $this->gateway->sent[0][1]);
    }

    public function test_invitation_link_can_be_found_without_a_selected_building(): void
    {
        $token = $this->invite();

        // Simulate a visitor who has no building selected (or has another building selected).
        $invitation = app(CurrentBuilding::class)->actingAs(999999, fn () => $this->service->find($token));

        $this->assertSame($this->building->id, $invitation->building_id);
    }

    public function test_invitation_token_tolerates_trailing_sms_link_punctuation(): void
    {
        $token = $this->invite();

        $invitation = $this->service->find($token . '.)');

        $this->assertSame($this->building->id, $invitation->building_id);
    }

    public function test_public_invitation_page_loads_without_a_selected_building(): void
    {
        $token = $this->invite();

        // Exercise the actual public HTTP route, not only the service method.
        $response = $this->get(route('invite.show', $token));

        $response->assertOk();
        $response->assertSee('برج یک');
        $response->assertSee($token);
    }

    public function test_accept_activates_membership(): void
    {
        $token = $this->invite();
        $user = User::where('mobile', '09121112233')->firstOrFail();

        $this->service->accept($token, $user);

        $this->assertSame('active', BuildingUserRole::where('user_id', $user->id)->firstOrFail()->status->value);
        $this->assertSame('active', $user->fresh()->status);
        $this->assertNotNull(app(CurrentBuilding::class)->withoutScope(fn () => Invitation::firstOrFail()->accepted_at));
    }

    public function test_invitation_is_bound_to_its_mobile(): void
    {
        $token = $this->invite();
        $other = User::create(['mobile' => '09129998877', 'status' => 'active']);

        $this->expectException(InvitationException::class);
        $this->service->accept($token, $other);
    }

    public function test_expired_or_used_invitation_is_rejected(): void
    {
        $token = $this->invite();
        app(CurrentBuilding::class)->withoutScope(fn () => Invitation::query()->update(['expires_at' => now()->subMinute()]));

        $this->expectException(InvitationException::class);
        $this->service->find($token);
    }

    public function test_cannot_invite_an_existing_active_member_again(): void
    {
        $token = $this->invite();
        $this->service->accept($token, User::where('mobile', '09121112233')->firstOrFail());

        $this->expectException(InvitationException::class);
        $this->invite();
    }

    public function test_existing_user_can_view_and_reject_invitation_from_inbox(): void
    {
        $user = User::create(['mobile' => '09121112233', 'first_name' => 'کاربر', 'last_name' => 'ثبت‌نام‌شده', 'status' => 'active']);
        $this->invite('09121112233');

        $this->actingAs($user)
            ->get(route('invitations.index'))
            ->assertOk()
            ->assertSee('دعوت‌نامه‌های من')
            ->assertSee('برج یک')
            ->assertSee('پذیرفتن دعوت')
            ->assertSee('رد کردن');

        $invitation = Invitation::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('invitations.reject', $invitation->id))
            ->assertRedirect(route('invitations.index'));

        $this->assertNotNull(Invitation::withoutGlobalScopes()->findOrFail($invitation->id)->rejected_at);
        $this->assertSame(
            'rejected',
            BuildingUserRole::where('user_id', $user->id)->firstOrFail()->status->value
        );

        // A rejected invitation cannot be reactivated; the inviter must send a fresh invitation.
        $this->actingAs($this->owner)->withSession(['current_building_id' => $this->building->id])
            ->post(route('team.activate', $user->id), ['scope' => 'members'])
            ->assertSessionHasErrors('general');

        $this->assertSame(
            'rejected',
            BuildingUserRole::where('user_id', $user->id)->firstOrFail()->status->value
        );

        $this->actingAs($this->owner)->withSession(['current_building_id' => $this->building->id])
            ->post(route('members.invite'), ['mobile' => $user->mobile, 'role' => 'tenant'])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'invited',
            BuildingUserRole::where('user_id', $user->id)->firstOrFail()->status->value
        );
        $this->assertSame(2, Invitation::withoutGlobalScopes()->where('user_id', $user->id)->count());
    }

    public function test_invitation_inbox_only_shows_current_users_invitations(): void
    {
        $this->invite('09121112233');
        $other = User::create(['mobile' => '09129998877', 'status' => 'active']);

        $this->actingAs($other)
            ->get(route('invitations.index'))
            ->assertOk()
            ->assertSee('دعوت‌نامه‌ی فعالی ندارید')
            ->assertDontSee('برج یک');
    }


    public function test_existing_user_can_accept_invitation_from_inbox(): void
    {
        $user = User::create(['mobile' => '09121112233', 'first_name' => 'کاربر', 'last_name' => 'ثبت‌نام‌شده', 'status' => 'active']);
        $this->invite('09121112233');
        $invitation = Invitation::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)
            ->post(route('invitations.accept', $invitation->id))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull(Invitation::withoutGlobalScopes()->findOrFail($invitation->id)->accepted_at);
        $this->assertSame(
            'active',
            BuildingUserRole::where('user_id', $user->id)->firstOrFail()->status->value
        );
    }

}
