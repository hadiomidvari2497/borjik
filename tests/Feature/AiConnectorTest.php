<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\AiAccessToken;
use App\Models\AiActionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiConnectorTest extends TestCase
{
    use RefreshDatabase;

    private function token(array $abilities = ['read:overview']): string
    {
        $plain = 'bj_ai_'.bin2hex(random_bytes(32));
        AiAccessToken::query()->create([
            'name' => 'test connector',
            'token_hash' => hash('sha256', $plain),
            'token_prefix' => substr($plain, 0, 15),
            'abilities' => $abilities,
            'expires_at' => now()->addDay(),
        ]);

        return $plain;
    }

    public function test_overview_requires_valid_bearer_token_and_returns_only_summary_data(): void
    {
        $this->getJson('/api/ai/v1/overview')->assertUnauthorized();

        $this->withToken($this->token())
            ->getJson('/api/ai/v1/overview')
            ->assertOk()
            ->assertJsonStructure(['data' => ['application', 'environment', 'checked_at', 'counts', 'operations']]);
    }

    public function test_token_cannot_call_endpoint_without_required_ability(): void
    {
        $this->withToken($this->token(['read:overview']))
            ->getJson('/api/ai/v1/incidents')
            ->assertForbidden();
    }

    public function test_action_requests_wait_for_explicit_admin_approval(): void
    {
        $token = $this->token(['actions:request']);

        $response = $this->withToken($token)->postJson('/api/ai/v1/actions', [
            'action' => 'run_health_check',
            'reason' => 'بررسی سلامت پس از خطای اخیر',
        ])->assertStatus(202)->assertJsonPath('data.status', 'pending');

        $id = $response->json('data.id');
        $this->assertDatabaseHas('ai_action_requests', ['id' => $id, 'status' => 'pending']);

        $admin = AdminUser::query()->create([
            'username' => 'ai-connector-admin',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.ai-connector.actions.approve', $id))
            ->assertSessionHasErrors('confirm_execution');

        $this->assertDatabaseHas('ai_action_requests', ['id' => $id, 'status' => 'pending']);
    }

    public function test_revoked_or_expired_tokens_are_rejected(): void
    {
        $plain = 'bj_ai_'.bin2hex(random_bytes(32));
        AiAccessToken::query()->create([
            'name' => 'expired connector',
            'token_hash' => hash('sha256', $plain),
            'token_prefix' => substr($plain, 0, 15),
            'abilities' => ['read:overview'],
            'expires_at' => now()->subMinute(),
        ]);

        $this->withToken($plain)->getJson('/api/ai/v1/overview')->assertUnauthorized();
    }
}
