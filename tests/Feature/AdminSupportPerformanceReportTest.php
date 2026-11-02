<?php

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\SupportTicketEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSupportPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_support_admin_can_view_performance_report_with_agent_metrics(): void
    {
        $admin = AdminUser::create([
            'username' => 'support-report-admin',
            'name' => 'کارشناس آزمایشی',
            'password' => 'secret-pass-1',
            'status' => 'active',
            'is_super' => true,
        ]);

        $ticket = SupportTicket::create([
            'number' => 'SP-TEST-000001',
            'user_id' => $this->createUser()->id,
            'assigned_admin_id' => $admin->id,
            'resolved_by_admin_id' => $admin->id,
            'subject' => 'آزمایش گزارش عملکرد',
            'category' => 'technical',
            'priority' => 'normal',
            'status' => 'resolved',
            'created_at' => now()->subHours(5),
            'updated_at' => now(),
            'first_response_at' => now()->subHours(4),
            'resolution_due_at' => now()->addHours(10),
            'closed_at' => now(),
        ]);

        SupportMessage::create([
            'support_ticket_id' => $ticket->id,
            'sender_type' => 'admin',
            'sender_id' => $admin->id,
            'body' => 'پاسخ آزمایشی',
        ]);

        SupportTicketEvent::create([
            'support_ticket_id' => $ticket->id,
            'event_type' => 'first_response',
            'actor_admin_id' => $admin->id,
            'description' => 'اولین پاسخ',
            'created_at' => now()->subHours(4),
        ]);
        SupportTicketEvent::create([
            'support_ticket_id' => $ticket->id,
            'event_type' => 'resolved',
            'actor_admin_id' => $admin->id,
            'description' => 'حل تیکت',
            'created_at' => now(),
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('admin.support.reports'))
            ->assertOk()
            ->assertSee('گزارش عملکرد پشتیبانی')
            ->assertSee('کارشناس آزمایشی')
            ->assertSee('میانگین زمان اولین پاسخ')
            ->assertSee('رعایت کلی SLA')
            ->assertSee('تعداد پاسخ‌ها');
    }

    public function test_support_admin_can_add_private_notes_and_manage_canned_replies(): void
    {
        $admin = AdminUser::create([
            'username' => 'support-tools-admin',
            'name' => 'کارشناس ابزارها',
            'password' => 'secret-pass-2',
            'status' => 'active',
            'is_super' => true,
        ]);
        $ticket = SupportTicket::create([
            'number' => 'SP-TEST-TOOLS-0001',
            'user_id' => $this->createUser()->id,
            'subject' => 'تست یادداشت داخلی',
            'category' => 'technical',
            'priority' => 'normal',
            'status' => 'open',
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.support.internal-note', $ticket), ['body' => 'این متن محرمانه تیم است'])
            ->assertRedirect(route('admin.support.show', $ticket));

        $this->assertDatabaseHas('support_messages', [
            'support_ticket_id' => $ticket->id,
            'body' => 'این متن محرمانه تیم است',
            'is_internal' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('admin.support.canned-replies.store'), [
                'title' => 'راهنمای ورود',
                'body' => 'برای ورود، شماره همراه خود را وارد کنید.',
            ])
            ->assertRedirect(route('admin.support.canned-replies'));

        $this->assertDatabaseHas('support_canned_replies', [
            'title' => 'راهنمای ورود',
            'is_active' => true,
        ]);
    }

    private function createUser(): \App\Models\User
    {
        return \App\Models\User::create([
            'first_name' => 'کاربر',
            'last_name' => 'آزمایشی',
            'mobile' => '09129990001',
            'status' => 'active',
        ]);
    }
}
