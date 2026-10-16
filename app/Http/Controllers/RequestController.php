<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAssignee;
use App\Models\TicketComment;
use App\Models\TicketEvent;
use App\Models\TicketStatusLog;
use App\Models\Unit;
use App\Models\User;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RequestController extends Controller
{
    private const CATEGORIES = [
        'general' => 'عمومی', 'maintenance' => 'تعمیرات', 'cleaning' => 'نظافت',
        'security' => 'حراست', 'facilities' => 'تأسیسات و مشاعات',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('request.view.all') || $user->hasPermission('request.view.domain'), 403);
        $query = Ticket::where('kind', 'request')->with(['createdBy', 'unit', 'assignees.user', 'acceptedBy'])->latest();
        if (! $user->hasPermission('request.view.all')) {
            $query->whereHas('assignees', fn ($q) => $q->where('user_id', $user->id));
        }
        return view('requests.index', [
            'tickets' => $query->paginate(15),
            'canManage' => $user->hasPermission('request.view.all'),
            'statuses' => $this->statusLabels(),
        ]);
    }

    public function show(Request $request, int $ticket)
    {
        $item = $this->visibleTicket($request, $ticket);
        return view('requests.show', [
            'ticket' => $item,
            'isMine' => $request->routeIs('my.requests.show'),
            'canManage' => $request->user()->hasPermission('request.view.all'),
            'canUpdateStatus' => $this->canUpdateStatus($request->user(), $item),
            'canClaim' => $this->canClaim($request->user(), $item),
            'staff' => $request->user()->hasPermission('request.view.all') ? $this->staffOptions($item->category) : collect(),
            'statuses' => $this->statusLabels(),
        ]);
    }

    public function myIndex(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('request.view.own') || $user->hasPermission('request.create'), 403);
        return view('my.requests', [
            'tickets' => Ticket::where('kind', 'request')->where('created_by', $user->id)
                ->with(['unit', 'assignees.user', 'acceptedBy'])->latest()->paginate(10),
            'categories' => self::CATEGORIES,
            'statuses' => $this->statusLabels(),
            'units' => Unit::query()
                ->when(! $user->hasPermission('request.view.all'), fn ($q) => $q->whereHas('parties', fn ($p) => $p->where('user_id', $user->id)->whereNull('to_date')))
                ->with('block')->orderBy('number')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('request.create'), 403);
        $buildingId = app(CurrentBuilding::class)->id();
        abort_unless($buildingId, 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'location' => ['nullable', 'string', 'max:150'],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('building_id', $buildingId)->whereNull('deleted_at')],
        ]);

        if (! empty($data['unit_id']) && ! $user->hasPermission('request.view.all')) {
            abort_unless(DB::table('unit_parties')->where('building_id', $buildingId)
                ->where('unit_id', $data['unit_id'])->where('user_id', $user->id)->whereNull('to_date')->exists(), 403);
        }

        $ticket = DB::transaction(function () use ($data, $user, $buildingId) {
            $roleKey = match ($data['category']) {
                'maintenance', 'facilities' => 'maintenance',
                'cleaning' => 'cleaning',
                'security' => 'security',
                default => null,
            };
            $roleId = $roleKey ? Role::where('key', $roleKey)->value('id') : null;
            $assigneeIds = $roleKey
                ? DB::table('building_user_roles as bur')->join('roles as r', 'r.id', '=', 'bur.role_id')
                    ->where('bur.building_id', $buildingId)->where('bur.status', 'active')
                    ->where('r.key', $roleKey)->pluck('bur.user_id')->unique()->values()
                : collect();
            $initialStatus = TicketStatus::New;

            $ticket = Ticket::create([
                'building_id' => $buildingId,
                'number' => 'REQ-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'kind' => 'request', 'title' => $data['title'], 'description' => $data['description'],
                'category' => $data['category'], 'priority' => $data['priority'],
                'location' => $data['location'] ?? null, 'unit_id' => $data['unit_id'] ?? null,
                'target_role_id' => $roleId, 'created_by' => $user->id, 'status' => $initialStatus->value,
            ]);

            foreach ($assigneeIds as $assigneeId) {
                TicketAssignee::create([
                    'building_id' => $buildingId, 'ticket_id' => $ticket->id,
                    'user_id' => $assigneeId, 'assigned_by' => null, 'assigned_at' => now(),
                ]);
            }
            TicketStatusLog::create([
                'building_id' => $buildingId, 'ticket_id' => $ticket->id,
                'from_status' => null, 'to_status' => $initialStatus->value, 'user_id' => $user->id,
                'note' => $assigneeIds->isNotEmpty()
                    ? 'درخواست ثبت شد؛ برای مدیر و پرسنل مرتبط قابل مشاهده است و هنوز مسئول مشخصی ندارد.'
                    : 'درخواست ثبت شد و برای بررسی مدیر ساختمان در صف قرار گرفت.',
                'created_at' => now(),
            ]);
            $this->recordEvent($ticket, $user, 'created', 'درخواست ثبت شد.');
            if ($assigneeIds->isNotEmpty()) $this->recordEvent($ticket, $user, 'staff_notified', 'درخواست برای پرسنل مرتبط قابل مشاهده شد؛ مسئولیت هنوز پذیرفته نشده است.');
            return $ticket;
        });

        return redirect()->route('my.requests.show', $ticket->id)
            ->with('status', 'درخواست شما ثبت شد و در پنل مدیریت ساختمان قرار گرفت.');
    }

    public function assign(Request $request, int $ticket)
    {
        $user = $request->user(); abort_unless($user->hasPermission('request.view.all'), 403);
        $buildingId = app(CurrentBuilding::class)->id();
        $data = $request->validate(['user_id' => ['nullable', 'integer', Rule::exists('building_user_roles', 'user_id')->where('building_id', $buildingId)->where('status', 'active')]]);
        DB::transaction(function () use ($ticket, $data, $user, $buildingId) {
            $item = Ticket::where('kind', 'request')->lockForUpdate()->findOrFail($ticket); $previous = $item->acceptedBy; $staffUser = null;
            $roleKey = match ($item->category) { 'maintenance', 'facilities' => 'maintenance', 'cleaning' => 'cleaning', 'security' => 'security', default => null };
            if (! empty($data['user_id'])) {
                abort_unless($roleKey, 422, 'برای درخواست عمومی مسئول تخصصی تعریف نشده است.');
                $staffUser = User::whereKey($data['user_id'])->whereExists(function ($query) use ($buildingId, $roleKey) { $query->selectRaw('1')->from('building_user_roles as bur')->join('roles as r', 'r.id', '=', 'bur.role_id')->whereColumn('bur.user_id', 'users.id')->where('bur.building_id', $buildingId)->where('bur.status', 'active')->where('r.key', $roleKey); })->firstOrFail();
            }
            $item->assignees()->delete();
            if ($staffUser) TicketAssignee::create(['building_id' => $buildingId, 'ticket_id' => $item->id, 'user_id' => $staffUser->id, 'assigned_by' => $user->id, 'assigned_at' => now(), 'accepted_at' => now()]);
            elseif ($roleKey) { $ids = DB::table('building_user_roles as bur')->join('roles as r', 'r.id', '=', 'bur.role_id')->where('bur.building_id', $buildingId)->where('bur.status', 'active')->where('r.key', $roleKey)->pluck('bur.user_id')->unique(); foreach ($ids as $id) TicketAssignee::create(['building_id' => $buildingId, 'ticket_id' => $item->id, 'user_id' => $id, 'assigned_by' => $user->id, 'assigned_at' => now()]); }
            $item->accepted_by = $staffUser?->id; $item->save();
            $from = $previous ? ($previous->display_name ?: $previous->mobile) : 'بدون مسئول'; $to = $staffUser ? ($staffUser->display_name ?: $staffUser->mobile) : 'صف عمومی پرسنل مرتبط';
            $this->recordEvent($item, $user, 'assignment_changed', "مدیر مسئول درخواست را از «{$from}» به «{$to}» تغییر داد.");
            if ($item->status === TicketStatus::New && $staffUser) $this->changeStatus($item, TicketStatus::Review, $user, 'درخواست توسط مدیر به مسئول مشخص ارجاع شد.');
        });
        return back()->with('status', 'مسئولیت درخواست به‌روزرسانی شد و تغییر در تاریخچه ثبت شد.');
    }

    public function claim(Request $request, int $ticket)
    {
        $user = $request->user(); abort_unless($user->hasPermission('ticket.respond'), 403);
        DB::transaction(function () use ($ticket, $user) {
            $item = Ticket::where('kind', 'request')->lockForUpdate()->findOrFail($ticket);
            abort_unless($item->assignees()->where('user_id', $user->id)->exists(), 403);
            if ($item->accepted_by !== null) abort(409, 'این درخواست قبلاً پذیرفته شده است.');
            $assignee = $item->assignees()->where('user_id', $user->id)->lockForUpdate()->firstOrFail(); $assignee->accepted_at = now(); $assignee->save();
            $item->accepted_by = $user->id; $item->save();
            if ($item->status === TicketStatus::New) $this->changeStatus($item, TicketStatus::Review, $user, 'پرسنل مسئولیت درخواست را پذیرفت.');
            $this->recordEvent($item, $user, 'claimed', 'مسئولیت درخواست توسط این نیرو پذیرفته شد.');
        });
        return back()->with('status', 'مسئولیت درخواست به شما واگذار شد.');
    }

    public function updateStatus(Request $request, int $ticket)
    {
        $item = $this->visibleTicket($request, $ticket);
        abort_unless($this->canUpdateStatus($request->user(), $item), 403);
        $data = $request->validate([
            'status' => ['required', Rule::in(array_map(fn ($s) => $s->value, TicketStatus::cases()))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $status = TicketStatus::from($data['status']);
        if (! $request->user()->hasPermission('request.view.all') && $status === TicketStatus::Closed) {
            abort(403);
        }
        $this->changeStatus($item, $status, $request->user(), $data['note'] ?? null);
        return back()->with('status', 'وضعیت درخواست به‌روزرسانی شد.');
    }

    public function comment(Request $request, int $ticket)
    {
        $item = $this->visibleTicket($request, $ticket);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000'], 'reply_to_id' => ['nullable', 'integer', Rule::exists('ticket_comments', 'id')->where('ticket_id', $item->id)]]);
        $comment = TicketComment::create(['building_id' => app(CurrentBuilding::class)->id(), 'ticket_id' => $item->id, 'user_id' => $request->user()->id, 'body' => $data['body'], 'reply_to_id' => $data['reply_to_id'] ?? null]);
        $this->recordEvent($item, $request->user(), 'commented', empty($data['reply_to_id']) ? 'پیام جدیدی در گفت‌وگو ثبت شد.' : 'پاسخی به یک پیام در گفت‌وگو ثبت شد.', ['comment_id' => $comment->id, 'reply_to_id' => $comment->reply_to_id]);
        return back()->with('status', 'پیام شما ثبت شد.');
    }

    private function visibleTicket(Request $request, int $id): Ticket
    {
        $ticket = Ticket::where('kind', 'request')
            ->with(['createdBy', 'unit', 'assignees.user', 'acceptedBy', 'comments.user', 'comments.replyTo.user', 'statusLogs.user', 'events.user'])->findOrFail($id);
        $user = $request->user();
        $creator = (int) $ticket->created_by === (int) $user->id;
        $assigned = $ticket->assignees->contains(fn ($a) => (int) $a->user_id === (int) $user->id);
        abort_unless($user->hasPermission('request.view.all') || $creator ||
            ($user->hasPermission('request.view.domain') && $assigned), 403);
        return $ticket;
    }

    private function canClaim(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('ticket.respond') && $ticket->accepted_by === null && $ticket->assignees->contains(fn ($a) => (int) $a->user_id === (int) $user->id);
    }

    private function canUpdateStatus(User $user, Ticket $ticket): bool
    {
        return $user->hasPermission('request.view.all') ||
            ($user->hasPermission('ticket.respond') && (int) $ticket->accepted_by === (int) $user->id);
    }

    private function changeStatus(Ticket $ticket, TicketStatus $status, User $user, ?string $note): void
    {
        $previous = $ticket->status;
        $ticket->status = $status;
        $ticket->closed_at = $status === TicketStatus::Closed ? now() : null;
        $ticket->save();
        TicketStatusLog::create([
            'building_id' => app(CurrentBuilding::class)->id(), 'ticket_id' => $ticket->id,
            'from_status' => $previous?->value, 'to_status' => $status->value,
            'user_id' => $user->id, 'note' => $note, 'created_at' => now(),
        ]);
        $this->recordEvent($ticket, $user, 'status_changed', 'وضعیت از «'.($previous?->value ?? '—').'» به «'.$status->value.'» تغییر کرد.'.($note ? ' '.$note : ''));
        if ($note !== null && trim($note) !== '') {
            TicketComment::create([
                'building_id' => app(CurrentBuilding::class)->id(), 'ticket_id' => $ticket->id,
                'user_id' => $user->id, 'body' => $note,
            ]);
        }
    }

    private function staffOptions(string $category)
    {
        $roleKey = match ($category) { 'maintenance', 'facilities' => 'maintenance', 'cleaning' => 'cleaning', 'security' => 'security', default => null };
        if (! $roleKey) return collect();
        return User::select('users.id', 'users.first_name', 'users.last_name', 'users.mobile')->join('building_user_roles as bur', 'bur.user_id', '=', 'users.id')->join('roles as r', 'r.id', '=', 'bur.role_id')->where('bur.building_id', app(CurrentBuilding::class)->id())->where('bur.status', 'active')->where('r.key', $roleKey)->whereNull('users.deleted_at')->distinct()->orderBy('users.first_name')->get();
    }

    private function recordEvent(Ticket $ticket, ?User $user, string $type, string $description, array $metadata = []): void
    {
        TicketEvent::create(['building_id' => $ticket->building_id, 'ticket_id' => $ticket->id, 'user_id' => $user?->id, 'type' => $type, 'description' => $description, 'metadata' => $metadata ?: null, 'created_at' => now()]);
    }

    private function statusLabels(): array
    {
        return ['new' => 'ثبت‌شده', 'review' => 'در حال بررسی', 'doing' => 'در حال انجام',
            'waiting' => 'در انتظار', 'done' => 'انجام‌شده', 'closed' => 'بسته‌شده'];
    }
}
