<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_ticket_gets_a_priority_suggestion_and_duplicate_match(): void
    {
        $existingUser = $this->createUser('09128880001');
        $existing = SupportTicket::create([
            'number' => 'SP-OLD-000001',
            'user_id' => $existingUser->id,
            'subject' => 'اختلال در ورود به پنل',
            'category' => 'technical',
            'priority' => 'normal',
            'status' => 'open',
        ]);
        SupportMessage::create([
            'support_ticket_id' => $existing->id,
            'sender_type' => 'user',
            'sender_id' => $existingUser->id,
            'body' => 'هنگام ورود به پنل خطای نام کاربری دریافت می‌کنم و نمی‌توانم وارد شوم.',
        ]);

        $user = $this->createUser('09128880002');
        $this->actingAs($user)->post(route('support.store'), [
            'subject' => 'مشکل ورود به پنل',
            'category' => 'technical',
            'body' => 'هنگام ورود به پنل خطای نام کاربری دریافت می‌کنم و نمی‌توانم وارد شوم لطفا بررسی کنید.',
        ])->assertRedirect();

        $created = SupportTicket::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('high', $created->priority_suggestion);
        $this->assertSame($existing->id, $created->duplicate_of_ticket_id);
        $this->assertGreaterThanOrEqual(58, $created->duplicate_match_score);
        $this->assertSame('normal', $created->priority);
    }

    public function test_admin_can_apply_suggested_priority_and_view_intelligence_report(): void
    {
        $admin = AdminUser::create([
            'username' => 'support-intelligence-admin',
            'name' => 'کارشناس هوشمند',
            'password' => 'secret-pass-3',
            'status' => 'active',
            'is_super' => true,
        ]);
        $user = $this->createUser('09128880003');
        $ticket = SupportTicket::create([
            'number' => 'SP-INTEL-000001',
            'user_id' => $user->id,
            'subject' => 'خطای پرداخت و کسر وجه',
            'category' => 'billing',
            'priority' => 'normal',
            'priority_suggestion' => 'urgent',
            'priority_suggestion_reason' => 'نشانه مالی حساس',
            'status' => 'open',
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.support.intelligence'))
            ->assertOk()
            ->assertSee('تحلیل هوشمند درخواست‌ها')
            ->assertSee('تیکت‌های تحلیل‌شده');

        $this->actingAs($admin, 'admin')
            ->post(route('admin.support.apply-priority-suggestion', $ticket))
            ->assertRedirect(route('admin.support.show', $ticket));

        $this->assertDatabaseHas('support_tickets', ['id' => $ticket->id, 'priority' => 'urgent']);
        $this->assertDatabaseHas('support_ticket_events', ['support_ticket_id' => $ticket->id, 'event_type' => 'priority_changed']);
    }


    public function test_existing_tickets_can_be_analyzed_by_artisan_command(): void
    {
        $user = $this->createUser('09128880004');
        $ticket = SupportTicket::create([
            'number' => 'SP-INTEL-OLD-0001',
            'user_id' => $user->id,
            'subject' => 'خطای پرداخت اشتراک',
            'category' => 'billing',
            'priority' => 'normal',
            'status' => 'open',
        ]);
        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'user',
            'sender_id' => $user->id,
            'body' => 'پرداخت ناموفق بوده و برای تمدید اشتراک خطا دریافت می‌کنم.',
        ]);

        $this->artisan('support:analyze-existing')->assertExitCode(0);

        $this->assertDatabaseHas('support_tickets', [
            'id' => $ticket->id,
            'priority_suggestion' => 'urgent',
        ]);
    }

    private function createUser(string $mobile): \App\Models\User
    {
        return \App\Models\User::create([
            'first_name' => 'کاربر',
            'last_name' => 'آزمایشی',
            'mobile' => $mobile,
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
    }
}
