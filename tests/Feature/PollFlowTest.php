<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Role;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PollFlowTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private User $owner;
    private User $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->owner = $this->makeUser('09120000011', 'owner');
        $this->building = Building::create(['name' => 'ساختمان رأی‌گیری', 'code' => '55111', 'created_by' => $this->owner->id]);
        $this->join($this->owner, 'owner');
        $this->tenant = $this->makeUser('09120000012', 'tenant');
        $this->join($this->tenant, 'tenant');
    }

    private function makeUser(string $mobile, string $role): User
    {
        return User::create(['mobile' => $mobile, 'first_name' => 'کاربر', 'last_name' => $role, 'status' => 'active', 'profile_completed_at' => now()]);
    }

    private function join(User $user, string $role): void
    {
        BuildingUserRole::create([
            'building_id' => $this->building->id,
            'user_id' => $user->id,
            'role_id' => Role::where('key', $role)->value('id'),
            'status' => 'active',
        ]);
    }

    private function open(User $user, string $method, string $uri, array $data = [])
    {
        return $this->actingAs($user)->withSession(['current_building_id' => $this->building->id])->{$method}($uri, $data);
    }

    public function test_manager_can_create_poll_and_options(): void
    {
        $this->open($this->owner, 'post', route('polls.store'), [
            'title' => 'انتخاب زمان تعمیر آسانسور',
            'description' => 'زمان مناسب را انتخاب کنید.',
            'audience' => 'residents',
            'starts_at' => now()->subMinute()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'options' => ['صبح', 'عصر'],
        ])->assertRedirect(route('polls.index'));

        $this->assertDatabaseHas('polls', ['building_id' => $this->building->id, 'title' => 'انتخاب زمان تعمیر آسانسور']);
        $this->assertDatabaseHas('poll_options', ['text' => 'صبح']);
        $this->assertDatabaseHas('poll_options', ['text' => 'عصر']);
    }

    public function test_eligible_member_can_vote_only_once_and_results_are_shown_after_vote(): void
    {
        $poll = $this->createPoll();
        $option = $this->firstOption($poll);

        $this->open($this->tenant, 'post', route('polls.vote', $poll->id), ['option_id' => $option->id])
            ->assertRedirect(route('polls.index'));

        $this->assertDatabaseCount('poll_votes', 1);

        $this->open($this->tenant, 'post', route('polls.vote', $poll->id), ['option_id' => $option->id])
            ->assertSessionHasErrors('general');

        $this->assertDatabaseCount('poll_votes', 1);
        $this->open($this->tenant, 'get', route('polls.index'))->assertOk()->assertSee('صبح')->assertSee('۱۰۰٪');
    }

    public function test_member_cannot_vote_for_option_from_another_poll(): void
    {
        $poll = $this->createPoll();
        $otherPoll = $this->createPoll('نظرسنجی دیگر');
        $foreignOption = $this->firstOption($otherPoll);

        $this->open($this->tenant, 'post', route('polls.vote', $poll->id), ['option_id' => $foreignOption->id])
            ->assertNotFound();
        $this->assertDatabaseCount('poll_votes', 0);
    }

    public function test_poll_management_is_not_available_to_tenant(): void
    {
        $this->open($this->tenant, 'post', route('polls.store'), [
            'title' => 'نباید ساخته شود',
            'audience' => 'all',
            'starts_at' => now()->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'options' => ['بله', 'خیر'],
        ])->assertForbidden();
    }

    public function test_manager_can_view_non_anonymous_voter_details(): void
    {
        $poll = $this->createPoll('نتایج شفاف');
        $option = $this->firstOption($poll);
        $this->open($this->tenant, 'post', route('polls.vote', $poll->id), ['option_id' => $option->id]);

        $this->open($this->owner, 'get', route('polls.show', $poll->id))
            ->assertOk()->assertSee('نتایج شفاف')->assertSee('صبح')->assertSee('tenant');
    }

    public function test_manager_cannot_see_identity_on_anonymous_poll_details(): void
    {
        $poll = app(CurrentBuilding::class)->actingAs($this->building->id, function (): Poll {
            $poll = Poll::create([
                'title' => 'رأی مخفی',
                'is_anonymous' => true,
                'audience' => 'all',
                'starts_at' => now()->subHour(),
                'ends_at' => now()->addDay(),
                'status' => 'open',
                'created_by' => $this->owner->id,
            ]);
            $poll->options()->create(['text' => 'بله']);
            $poll->options()->create(['text' => 'خیر']);
            return $poll;
        }, ['owner']);
        $option = $this->firstOption($poll);
        $this->open($this->tenant, 'post', route('polls.vote', $poll->id), ['option_id' => $option->id]);

        $this->open($this->owner, 'get', route('polls.show', $poll->id))
            ->assertOk()->assertSee('این نظرسنجی مخفی است')->assertDontSee('کاربر tenant');
    }

    public function test_member_can_submit_a_text_response_to_an_open_text_poll(): void
    {
        $poll = app(CurrentBuilding::class)->actingAs($this->building->id, function (): Poll {
            return Poll::create([
                'title' => 'نظر آزاد',
                'response_type' => 'text',
                'audience' => 'all',
                'starts_at' => now()->subHour(),
                'ends_at' => now()->addDay(),
                'status' => 'open',
                'created_by' => $this->owner->id,
            ]);
        }, ['owner']);

        $this->open($this->tenant, 'post', route('polls.vote', $poll->id), ['answer' => 'بهتر است تعمیر صبح انجام شود.'])
            ->assertRedirect(route('polls.index'));
        $this->assertDatabaseHas('poll_votes', [
            'poll_id' => $poll->id,
            'answer' => 'بهتر است تعمیر صبح انجام شود.',
            'option_id' => null,
        ]);
    }

    private function firstOption(Poll $poll): PollOption
    {
        return app(CurrentBuilding::class)->actingAs($this->building->id, fn () => $poll->options()->firstOrFail(), ['owner']);
    }

    private function createPoll(string $title = 'زمان تعمیر'): Poll
    {
        return app(CurrentBuilding::class)->actingAs($this->building->id, function () use ($title): Poll {
            $poll = Poll::create([
                'title' => $title,
                'audience' => 'all',
                'starts_at' => now()->subHour(),
                'ends_at' => now()->addDay(),
                'status' => 'open',
                'created_by' => $this->owner->id,
            ]);
            $poll->options()->create(['text' => 'صبح']);
            $poll->options()->create(['text' => 'عصر']);

            return $poll;
        }, ['owner']);
    }
}
