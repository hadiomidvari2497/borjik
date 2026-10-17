<?php

namespace Tests\Feature;

use App\Models\Building;
use App\Models\BuildingUserRole;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketAssignee;
use App\Models\User;
use App\Support\CurrentBuilding;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketAttachmentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Building $building;
    private User $manager;
    private User $maintenance;
    private User $cleaning;
    private BuildingUserRole $managerMembership;
    private BuildingUserRole $maintenanceMembership;
    private BuildingUserRole $cleaningMembership;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(RolePermissionSeeder::class);

        $this->manager = $this->makeUser('09122220001', 'مدیر');
        $this->building = Building::create([
            'name' => 'برج پیوست‌ها',
            'code' => 'ATT001',
            'created_by' => $this->manager->id,
        ]);
        $this->managerMembership = $this->membership($this->manager, 'manager');
        $this->maintenance = $this->makeUser('09122220002', 'تعمیرات');
        $this->maintenanceMembership = $this->membership($this->maintenance, 'maintenance');
        $this->cleaning = $this->makeUser('09122220003', 'نظافت');
        $this->cleaningMembership = $this->membership($this->cleaning, 'cleaning');
    }

    public function test_attachment_download_requires_access_to_the_parent_task(): void
    {
        $ticket = $this->makeTask($this->building, $this->manager);
        $attachment = $this->makeAttachment($ticket, $this->manager);

        $this->actingAs($this->cleaning)->withSession($this->sessionFor($this->cleaningMembership))
            ->get(route('tickets.attachments.show', [$ticket->id, $attachment->id]))
            ->assertForbidden();

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->get(route('tickets.attachments.show', [$ticket->id, $attachment->id]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_attachment_id_cannot_be_used_with_another_task_id(): void
    {
        $firstTicket = $this->makeTask($this->building, $this->manager);
        $secondTicket = $this->makeTask($this->building, $this->manager);
        $attachment = $this->makeAttachment($secondTicket, $this->manager);

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->get(route('tickets.attachments.show', [$firstTicket->id, $attachment->id]))
            ->assertNotFound();
    }

    public function test_attachment_from_another_building_cannot_be_downloaded_by_changing_ticket_or_attachment_id(): void
    {
        $otherBuilding = Building::create([
            'name' => 'برج دیگر',
            'code' => 'ATT002',
            'created_by' => $this->manager->id,
        ]);

        $otherTicket = $this->makeTask($otherBuilding, $this->manager);
        $otherAttachment = $this->makeAttachment($otherTicket, $this->manager);

        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->get(route('tickets.attachments.show', [$otherTicket->id, $otherAttachment->id]))
            ->assertNotFound();

        $ownTicket = $this->makeTask($this->building, $this->manager);
        $this->actingAs($this->manager)->withSession($this->sessionFor($this->managerMembership))
            ->get(route('tickets.attachments.show', [$ownTicket->id, $otherAttachment->id]))
            ->assertNotFound();
    }

    private function makeTask(Building $building, User $creator): Ticket
    {
        return app(CurrentBuilding::class)->actingAs($building->id, function () use ($building, $creator) {
            return Ticket::create([
                'building_id' => $building->id,
                'number' => 'TSK-'.str_pad((string) (Ticket::withoutGlobalScopes()->count() + 1), 3, '0', STR_PAD_LEFT),
                'kind' => 'task',
                'title' => 'تسک آزمایشی',
                'description' => 'شرح تست پیوست',
                'category' => 'maintenance',
                'priority' => 'medium',
                'status' => 'new',
                'created_by' => $creator->id,
            ]);
        });
    }

    private function makeAttachment(Ticket $ticket, User $uploader): TicketAttachment
    {
        $path = 'ticket-attachments/'.$ticket->building_id.'/'.$ticket->id.'/private-test.pdf';
        Storage::disk('local')->put($path, 'private attachment content');

        return app(CurrentBuilding::class)->actingAs($ticket->building_id, function () use ($ticket, $uploader, $path) {
            return TicketAttachment::create([
                'building_id' => $ticket->building_id,
                'ticket_id' => $ticket->id,
                'uploaded_by' => $uploader->id,
                'path' => $path,
                'original_name' => 'private-test.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => 26,
            ]);
        });
    }

    private function makeUser(string $mobile, string $name): User
    {
        return User::create([
            'mobile' => $mobile,
            'first_name' => $name,
            'last_name' => 'تست',
            'status' => 'active',
            'profile_completed_at' => now(),
        ]);
    }

    private function membership(User $user, string $role): BuildingUserRole
    {
        return BuildingUserRole::create([
            'building_id' => $this->building->id,
            'user_id' => $user->id,
            'role_id' => Role::where('key', $role)->value('id'),
            'status' => 'active',
        ]);
    }

    private function sessionFor(BuildingUserRole $membership): array
    {
        return [
            'current_building_id' => $this->building->id,
            'current_building_user_role_id' => $membership->id,
        ];
    }
}
