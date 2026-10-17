<?php

namespace App\Http\Controllers;

use App\Enums\TicketKind;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Role;
use App\Models\Ticket;
use App\Models\TicketAssignee;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketEvent;
use App\Models\TicketRating;
use App\Models\TicketStatusLog;
use App\Models\Unit;
use App\Models\User;
use App\Notifications\RequestAlertNotification;
use App\Services\Sms\SmsSender;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    private const CATEGORIES = [
        'maintenance' => 'تعمیرات',
        'cleaning' => 'نظافت',
        'security' => 'حراست',
        'facilities' => 'تأسیسات و مشاعات',
        'admin' => 'اداری',
        'general' => 'عمومی',
    ];

    private const STATUSES = [
        'new' => 'جدید',
        'review' => 'در حال بررسی',
        'doing' => 'در حال انجام',
        'waiting' => 'در انتظار',
        'done' => 'پایان کار؛ در انتظار تأیید مدیر',
        'closed' => 'تأیید مدیر و بسته‌شده',
    ];

    private const PRIORITIES = [
        'urgent' => 'اضطراری',
        'high' => 'بالا',
        'medium' => 'متوسط',
        'low' => 'پایین',
    ];

    public function index(Request $request)
    {
        $user = $request->user();
        $canViewAll = $user->hasPermission('ticket.view.all');
        abort_unless($canViewAll || $user->hasPermission('ticket.view.assigned'), 403);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUSES))],
            'priority' => ['nullable', Rule::in(array_keys(self::PRIORITIES))],
            'category' => ['nullable', Rule::in(array_keys(self::CATEGORIES))],
            'assignee' => ['nullable', 'integer'],
            'overdue' => ['nullable', Rule::in(['1'])],
            'view' => ['nullable', Rule::in(['list', 'kanban'])],
        ]);

        $base = Ticket::query()->where('kind', TicketKind::Task->value);
        if (! $canViewAll) {
            $base->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                    ->orWhereHas('assignees', fn ($a) => $a->where('user_id', $user->id));
            });
        }

        $stats = [
            'total' => (clone $base)->count(),
            'open' => (clone $base)->whereNotIn('status', ['done', 'closed'])->count(),
            'overdue' => (clone $base)->whereNotIn('status', ['done', 'closed'])->whereNotNull('deadline')->where('deadline', '<', now())->count(),
            'done' => (clone $base)->whereIn('status', ['done', 'closed'])->count(),
        ];

        $query = (clone $base)->with(['createdBy', 'unit.block', 'acceptedBy', 'assignees.user'])->latest();
        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(fn ($q) => $q->where('number', 'like', "%{$term}%")
                ->orWhere('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('location', 'like', "%{$term}%"));
        }
        $query->when($filters['status'] ?? null, fn ($q, $value) => $q->where('status', $value))
            ->when($filters['priority'] ?? null, fn ($q, $value) => $q->where('priority', $value))
            ->when($filters['category'] ?? null, fn ($q, $value) => $q->where('category', $value))
            ->when($filters['assignee'] ?? null, fn ($q, $value) => $q->whereHas('assignees', fn ($a) => $a->where('user_id', $value)))
            ->when(($filters['overdue'] ?? null) === '1', fn ($q) => $q->whereNotIn('status', ['done', 'closed'])->whereNotNull('deadline')->where('deadline', '<', now()));

        $staff = $this->staffOptions();
        $kanban = ($filters['view'] ?? 'kanban') === 'kanban';
        $tickets = $kanban ? $query->get() : $query->paginate(20)->withQueryString();

        return view('tickets.index', [
            'tickets' => $tickets,
            'staff' => $staff,
            'stats' => $stats,
            'filters' => $filters,
            'statuses' => self::STATUSES,
            'categories' => self::CATEGORIES,
            'priorities' => self::PRIORITIES,
            'canManage' => $canViewAll && $user->hasPermission('ticket.create'),
            'kanban' => $kanban,
        ]);
    }

    public function create(Request $request)
    {
        abort_unless($request->user()->hasPermission('ticket.create'), 403);

        return view('tickets.form', [
            'ticket' => null,
            'statuses' => self::STATUSES,
            'categories' => self::CATEGORIES,
            'priorities' => self::PRIORITIES,
            'staff' => $this->staffOptions(),
            'roles' => $this->roleOptions(),
            'units' => Unit::query()->with('block')->orderBy('number')->get(),
            'selectedUserIds' => [],
            'targetRoleId' => null,
        ]);
    }

    public function edit(Request $request, int $ticket)
    {
        abort_unless($request->user()->hasPermission('ticket.view.all') && $request->user()->hasPermission('ticket.create'), 403);
        $item = Ticket::query()->where('kind', TicketKind::Task->value)
            ->with('assignees')->findOrFail($ticket);

        return view('tickets.form', [
            'ticket' => $item,
            'statuses' => self::STATUSES,
            'categories' => self::CATEGORIES,
            'priorities' => self::PRIORITIES,
            'staff' => $this->staffOptions(),
            'roles' => Role::query()->where('is_staff', true)->orderBy('name_fa')->get(),
            'units' => Unit::query()->with('block')->orderBy('number')->get(),
            'selectedUserIds' => $item->assignees->pluck('user_id')->map(fn ($id) => (int) $id)->all(),
            'targetRoleId' => $item->target_role_id,
        ]);
    }

    public function store(Request $request, SmsSender $sms)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('ticket.create'), 403);
        $buildingId = app(CurrentBuilding::class)->id();
        abort_unless($buildingId, 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'priority' => ['required', Rule::in(array_keys(self::PRIORITIES))],
            'deadline' => ['nullable', 'date', 'after_or_equal:today'],
            'location' => ['nullable', 'string', 'max:150'],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('building_id', $buildingId)->whereNull('deleted_at')],
            'target_role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('is_staff', true)->where('is_active', true)],
            'user_ids' => ['nullable', 'array', 'max:50'],
            'user_ids.*' => ['integer', 'distinct'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $roleId = $data['target_role_id'] ?? null;
        if ($roleId && Role::where('building_id', $buildingId)->where('is_staff', true)->exists()) {
            abort_unless(Role::where('building_id', $buildingId)->where('is_staff', true)->where('is_active', true)->whereKey($roleId)->exists(), 422, 'نقش انتخاب‌شده متعلق به این ساختمان نیست.');
        }
        $userIds = collect($data['user_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($roleId) {
            $userIds = $userIds->merge(DB::table('building_user_roles')
                ->where('building_id', $buildingId)->where('role_id', $roleId)->where('status', 'active')
                ->pluck('user_id')->map(fn ($id) => (int) $id))->unique()->values();
        }
        $validIds = DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.building_id', $buildingId)->where('bur.status', 'active')
            ->where('r.is_staff', true)->whereIn('bur.user_id', $userIds)
            ->pluck('bur.user_id')->unique()->map(fn ($id) => (int) $id)->values();
        abort_if($validIds->count() !== $userIds->count(), 422, 'یکی از افراد انتخاب‌شده، پرسنل فعال این ساختمان نیست.');

        $ticket = DB::transaction(function () use ($data, $user, $buildingId, $roleId, $validIds, $request) {
            $ticket = Ticket::create([
                'building_id' => $buildingId,
                'number' => 'TSK-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'kind' => TicketKind::Task->value,
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'],
                'priority' => $data['priority'],
                'deadline' => $data['deadline'] ?? null,
                'location' => $data['location'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'target_role_id' => $roleId,
                'created_by' => $user->id,
                'status' => TicketStatus::New->value,
            ]);

            foreach ($validIds as $staffId) {
                TicketAssignee::create([
                    'building_id' => $buildingId,
                    'ticket_id' => $ticket->id,
                    'user_id' => $staffId,
                    'assigned_by' => $user->id,
                    'assigned_at' => now(),
                ]);
            }

            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store("ticket-attachments/{$buildingId}/{$ticket->id}", 'local');
                TicketAttachment::create([
                    'building_id' => $buildingId,
                    'ticket_id' => $ticket->id,
                    'uploaded_by' => $user->id,
                    'path' => $path,
                    'original_name' => Str::limit(basename($file->getClientOriginalName()), 255, ''),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $file->getSize(),
                ]);
            }

            TicketStatusLog::create([
                'building_id' => $buildingId, 'ticket_id' => $ticket->id,
                'from_status' => null, 'to_status' => TicketStatus::New->value,
                'user_id' => $user->id, 'note' => 'تسک ایجاد شد.', 'created_at' => now(),
            ]);
            TicketEvent::create([
                'building_id' => $buildingId, 'ticket_id' => $ticket->id,
                'user_id' => $user->id, 'type' => 'created',
                'description' => 'تسک ایجاد شد.'.($validIds->isNotEmpty() ? ' برای پرسنل انتخاب‌شده ارجاع شد.' : ' هنوز مسئول مشخصی ندارد.'),
                'metadata' => ['assignee_ids' => $validIds->all(), 'target_role_id' => $roleId],
                'created_at' => now(),
            ]);

            return $ticket;
        });

        $recipients = User::whereIn('id', $validIds)->get();
        foreach ($recipients as $recipient) {
            $recipient->notify(new RequestAlertNotification(
                buildingId: $buildingId,
                ticketId: $ticket->id,
                event: 'task_created',
                title: 'تسک جدید به شما ارجاع شد',
                message: 'تسک «'.$ticket->title.'» برای شما ثبت شده است.',
                url: route('tickets.show', $ticket->id),
            ));
            try {
                $sms->sendTemplate('task_created', $recipient->mobile, [
                    'title' => $ticket->title, 'number' => $ticket->number,
                ], $recipient->id, $buildingId);
            } catch (\InvalidArgumentException) {
                // Keep task creation resilient if a deployment has not yet refreshed its SMS templates.
            }
        }

        return redirect()->route('tickets.show', $ticket->id)->with('status', 'تسک با موفقیت ثبت شد.');
    }

    public function update(Request $request, int $ticket, SmsSender $sms)
    {
        $item = $this->visibleTask($request, $ticket);
        $user = $request->user();
        abort_unless($user->hasPermission('ticket.view.all') && $user->hasPermission('ticket.create'), 403);
        $buildingId = (int) $item->building_id;

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['required', 'string', 'max:10000'],
            'category' => ['required', Rule::in(array_keys(self::CATEGORIES))],
            'priority' => ['required', Rule::in(array_keys(self::PRIORITIES))],
            'deadline' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:150'],
            'unit_id' => ['nullable', 'integer', Rule::exists('units', 'id')->where('building_id', $buildingId)->whereNull('deleted_at')],
            'target_role_id' => ['nullable', 'integer', Rule::exists('roles', 'id')->where('building_id', $buildingId)->where('is_staff', true)->where('is_active', true)],
            'user_ids' => ['nullable', 'array', 'max:50'],
            'user_ids.*' => ['integer', 'distinct'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $roleId = $data['target_role_id'] ?? null;
        $userIds = collect($data['user_ids'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        if ($roleId) {
            $userIds = $userIds->merge(DB::table('building_user_roles')
                ->where('building_id', $buildingId)->where('role_id', $roleId)->where('status', 'active')
                ->pluck('user_id')->map(fn ($id) => (int) $id))->unique()->values();
        }
        $validIds = DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.building_id', $buildingId)->where('bur.status', 'active')
            ->where('r.is_staff', true)->whereIn('bur.user_id', $userIds)
            ->pluck('bur.user_id')->unique()->map(fn ($id) => (int) $id)->values();
        abort_if($validIds->count() !== $userIds->count(), 422, 'فقط پرسنل فعال همین ساختمان قابل ارجاع هستند.');

        $oldAssigneeIds = [];
        DB::transaction(function () use ($item, $data, $user, $buildingId, $roleId, $validIds, $request, &$oldAssigneeIds) {
            $locked = Ticket::query()->where('kind', TicketKind::Task->value)->lockForUpdate()->findOrFail($item->id);
            $currentAssignees = TicketAssignee::query()
                ->withoutGlobalScopes()
                ->where('building_id', $buildingId)
                ->where('ticket_id', $locked->id)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (TicketAssignee $assignee) => (int) $assignee->user_id);
            $oldAssigneeIds = $currentAssignees->keys()->map(fn ($id) => (int) $id)->all();

            $locked->fill([
                'title' => $data['title'],
                'description' => $data['description'],
                'category' => $data['category'],
                'priority' => $data['priority'],
                'deadline' => $data['deadline'] ?? null,
                'location' => $data['location'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'target_role_id' => $roleId,
            ]);

            // If the primary assignee is removed, clear the claim so a new assignee can accept it.
            if ($locked->accepted_by !== null && ! $validIds->contains((int) $locked->accepted_by)) {
                $locked->accepted_by = null;
            }
            $locked->save();

            // Keep existing assignment rows (and accepted_at) for staff who remain assigned.
            foreach ($currentAssignees as $assignedUserId => $assignee) {
                if (! $validIds->contains((int) $assignedUserId)) {
                    $assignee->delete();
                }
            }
            foreach ($validIds as $staffId) {
                if (! $currentAssignees->has($staffId)) {
                    TicketAssignee::create([
                        'building_id' => $buildingId,
                        'ticket_id' => $locked->id,
                        'user_id' => $staffId,
                        'assigned_by' => $user->id,
                        'assigned_at' => now(),
                    ]);
                }
            }

            foreach ($request->file('attachments', []) as $file) {
                $path = $file->store("ticket-attachments/{$buildingId}/{$locked->id}", 'local');
                TicketAttachment::create([
                    'building_id' => $buildingId,
                    'ticket_id' => $locked->id,
                    'uploaded_by' => $user->id,
                    'path' => $path,
                    'original_name' => Str::limit(basename($file->getClientOriginalName()), 255, ''),
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'size_bytes' => $file->getSize(),
                ]);
            }

            TicketEvent::create([
                'building_id' => $buildingId,
                'ticket_id' => $locked->id,
                'user_id' => $user->id,
                'type' => 'updated',
                'description' => 'جزئیات تسک و فهرست مسئولان توسط مدیر به‌روزرسانی شد.',
                'metadata' => [
                    'previous_assignee_ids' => $oldAssigneeIds,
                    'current_assignee_ids' => $validIds->all(),
                    'target_role_id' => $roleId,
                ],
                'created_at' => now(),
            ]);
        });

        $item->refresh();
        $newAssigneeIds = $validIds->diff($oldAssigneeIds);
        foreach (User::whereIn('id', $newAssigneeIds)->get() as $recipient) {
            $recipient->notify(new RequestAlertNotification(
                buildingId: $buildingId,
                ticketId: (int) $item->id,
                event: 'task_assigned',
                title: 'تسک به‌روزرسانی و به شما ارجاع شد',
                message: 'شما به تسک «'.$item->title.'» ارجاع شده‌اید.',
                url: route('tickets.show', $item->id),
            ));
            try {
                $sms->sendTemplate('task_assigned', $recipient->mobile, [
                    'title' => $item->title, 'number' => $item->number,
                ], $recipient->id, $buildingId);
            } catch (\InvalidArgumentException) {
                // Task updates should still succeed if the SMS template was disabled or removed.
            }
        }

        return redirect()->route('tickets.show', $item->id)->with('status', 'تسک و مسئولان آن به‌روزرسانی شدند.');
    }

    public function show(Request $request, int $ticket)
    {
        $item = $this->visibleTask($request, $ticket);
        $user = $request->user();
        $isManager = $user->hasPermission('ticket.view.all');
        $isAssigned = $item->assignees->contains(fn ($a) => (int) $a->user_id === (int) $user->id);
        $unratedAssignee = $item->assignees->contains(fn ($assignee) =>
            (int) $assignee->user_id !== (int) $user->id
            && ! $item->ratings->contains(fn ($rating) =>
                (int) $rating->rated_by === (int) $user->id
                && (int) $rating->rated_user_id === (int) $assignee->user_id
            )
        );
        $canRate = (int) $item->created_by === (int) $user->id
            && $item->status->value === 'closed'
            && $unratedAssignee;

        return view('tickets.show', [
            'ticket' => $item,
            'statuses' => self::STATUSES,
            'categories' => self::CATEGORIES,
            'priorities' => self::PRIORITIES,
            'staff' => $this->staffOptions(),
            'canManage' => $isManager && $user->hasPermission('ticket.create'),
            'isManager' => $isManager,
            'canUpdateStatus' => $isManager || ($user->hasPermission('ticket.respond') && $isAssigned),
            'canAddHelper' => $user->hasPermission('ticket.respond') && $isAssigned && ! in_array($item->status->value, ['closed'], true),
            'canRate' => $canRate,
            'existingRatings' => $item->ratings->where('rated_by', $user->id)->keyBy('rated_user_id'),
        ]);
    }

    public function claim(Request $request, int $ticket)
    {
        $item = $this->visibleTask($request, $ticket);
        $user = $request->user();
        abort_unless($user->hasPermission('ticket.respond'), 403);
        DB::transaction(function () use ($item, $user) {
            $locked = Ticket::query()->where('kind', TicketKind::Task->value)->lockForUpdate()->findOrFail($item->id);
            $assignee = $locked->assignees()->where('user_id', $user->id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($locked->status->value, ['done', 'closed'], true), 409, 'تسک پایان‌یافته یا بسته‌شده قابل پذیرش نیست.');
            abort_if($locked->accepted_by !== null, 409, 'این تسک قبلاً پذیرفته شده است.');

            $assignee->accepted_at = now();
            $assignee->save();
            if ($locked->accepted_by === null) {
                $locked->accepted_by = $user->id;
                if ($locked->status->value === 'new') {
                    $locked->status = TicketStatus::Review;
                    TicketStatusLog::create([
                        'building_id' => $locked->building_id, 'ticket_id' => $locked->id,
                        'from_status' => 'new', 'to_status' => 'review',
                        'user_id' => $user->id, 'note' => 'پرسنل مسئولیت تسک را پذیرفت.', 'created_at' => now(),
                    ]);
                }
                $locked->save();
            }
            TicketEvent::create([
                'building_id' => $locked->building_id, 'ticket_id' => $locked->id,
                'user_id' => $user->id, 'type' => 'claimed',
                'description' => 'مسئولیت تسک توسط '.($user->display_name ?: $user->mobile).' پذیرفته شد.',
                'metadata' => null, 'created_at' => now(),
            ]);
        });

        return back()->with('status', 'مسئولیت تسک به شما واگذار شد.');
    }

    public function comment(Request $request, int $ticket)
    {
        $item = $this->visibleTask($request, $ticket);
        $data = $request->validate(['body' => ['required', 'string', 'max:5000']]);
        $user = $request->user();

        $comment = TicketComment::create([
            'building_id' => $item->building_id,
            'ticket_id' => $item->id,
            'user_id' => $user->id,
            'body' => trim($data['body']),
        ]);
        TicketEvent::create([
            'building_id' => $item->building_id, 'ticket_id' => $item->id,
            'user_id' => $user->id, 'type' => 'commented',
            'description' => 'پیام جدیدی در گفت‌وگوی تسک ثبت شد.',
            'metadata' => ['comment_id' => $comment->id], 'created_at' => now(),
        ]);

        $recipients = collect([$item->createdBy])->merge($item->assignees->pluck('user'));
        $recipients = $recipients->filter()->unique('id')
            ->reject(fn ($recipient) => (int) $recipient->id === (int) $user->id);
        foreach ($recipients as $recipient) {
            $recipient->notify(new RequestAlertNotification(
                buildingId: (int) $item->building_id,
                ticketId: (int) $item->id,
                event: 'task_commented',
                title: 'پیام جدید در تسک',
                message: ($user->display_name ?: $user->mobile).' در تسک «'.$item->title.'» پیام گذاشت.',
                url: route('tickets.show', $item->id),
            ));
        }

        return back()->with('status', 'پیام شما در تاریخچه تسک ثبت شد.');
    }

    public function updateStatus(Request $request, int $ticket, SmsSender $sms)
    {
        $item = $this->visibleTask($request, $ticket);
        $user = $request->user();
        $isManager = $user->hasPermission('ticket.view.all');
        $assigned = $item->assignees->contains(fn ($a) => (int) $a->user_id === (int) $user->id);
        abort_unless($isManager || ($user->hasPermission('ticket.respond') && $assigned), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(self::STATUSES))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        $newStatus = $data['status'];
        $changed = DB::transaction(function () use ($item, $user, $data, $newStatus, $isManager) {
            $locked = Ticket::query()->where('kind', TicketKind::Task->value)->lockForUpdate()->findOrFail($item->id);
            abort_unless($isManager || ($user->hasPermission('ticket.respond') && $locked->assignees()->where('user_id', $user->id)->exists()), 403);
            $oldStatus = $locked->status->value;
            if ($oldStatus === $newStatus) {
                return false;
            }
            abort_unless($this->allowsStatusTransition($oldStatus, $newStatus, $isManager), 422, 'این تغییر وضعیت مجاز نیست.');

            $locked->status = $newStatus;
            $locked->closed_at = $newStatus === 'closed' ? now() : null;
            $locked->save();
            TicketStatusLog::create([
                'building_id' => $locked->building_id, 'ticket_id' => $locked->id,
                'from_status' => $oldStatus, 'to_status' => $newStatus,
                'user_id' => $user->id, 'note' => $data['note'] ?? null, 'created_at' => now(),
            ]);
            TicketEvent::create([
                'building_id' => $locked->building_id, 'ticket_id' => $locked->id,
                'user_id' => $user->id, 'type' => 'status_changed',
                'description' => 'وضعیت تسک از «'.(self::STATUSES[$oldStatus] ?? $oldStatus).'» به «'.self::STATUSES[$newStatus].'» تغییر کرد.'.(! empty($data['note']) ? ' '.$data['note'] : ''),
                'metadata' => ['from' => $oldStatus, 'to' => $newStatus],
                'created_at' => now(),
            ]);
            if (! empty($data['note'])) {
                TicketComment::create([
                    'building_id' => $locked->building_id, 'ticket_id' => $locked->id,
                    'user_id' => $user->id, 'body' => trim($data['note']),
                ]);
            }
            return true;
        });

        if (! $changed) {
            return back()->with('status', 'وضعیت تغییری نکرد.');
        }
        $item->refresh();
        $recipients = collect([$item->createdBy])
            ->merge($item->assignees->pluck('user'))
            ->filter()->unique('id')->reject(fn ($recipient) => (int) $recipient->id === (int) $user->id);
        foreach ($recipients as $recipient) {
            $recipient->notify(new RequestAlertNotification(
                buildingId: (int) $item->building_id,
                ticketId: (int) $item->id,
                event: $newStatus === 'done' ? 'task_awaiting_manager_approval' : ($newStatus === 'closed' ? 'task_approved' : 'task_status_changed'),
                title: $newStatus === 'done' ? 'تسک آماده تأیید نهایی است' : ($newStatus === 'closed' ? 'پایان کار تأیید شد' : 'وضعیت تسک تغییر کرد'),
                message: 'وضعیت تسک «'.$item->title.'» به «'.self::STATUSES[$newStatus].'» تغییر کرد.',
                url: route('tickets.show', $item->id),
            ));
            try {
                $sms->sendTemplate('task_status_changed', $recipient->mobile, [
                    'title' => $item->title, 'status' => self::STATUSES[$newStatus],
                ], $recipient->id, (int) $item->building_id);
            } catch (\InvalidArgumentException) {
                // SMS is optional; missing template must not break the workflow.
            }
        }

        return back()->with('status', 'وضعیت تسک به‌روزرسانی شد.');
    }

    private function allowsStatusTransition(string $from, string $to, bool $isManager): bool
    {
        // "done" means the assignee reported completion; only a manager can approve and close it.
        if ($to === 'closed') {
            return $isManager && $from === 'done';
        }
        if ($from === 'closed') {
            return $isManager && $to === 'doing';
        }
        if ($from === 'done') {
            return $isManager && $to === 'doing';
        }

        return match ($from) {
            'new' => $to === 'review',
            'review' => in_array($to, ['doing', 'waiting', 'done'], true),
            'doing' => in_array($to, ['waiting', 'done'], true),
            'waiting' => in_array($to, ['doing', 'done'], true),
            default => false,
        };
    }

    public function assign(Request $request, int $ticket)
    {
        $item = $this->visibleTask($request, $ticket);
        $user = $request->user();
        $isManager = $user->hasPermission('ticket.view.all') && $user->hasPermission('ticket.create');
        $isAssigned = $item->assignees->contains(fn ($a) => (int) $a->user_id === (int) $user->id);
        abort_unless($isManager || ($user->hasPermission('ticket.respond') && $isAssigned), 403);

        $buildingId = (int) $item->building_id;
        $data = $request->validate([
            'user_ids' => ['required', 'array', 'min:1', 'max:50'],
            'user_ids.*' => ['required', 'integer', 'distinct'],
        ]);
        $validIds = DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.building_id', $buildingId)->where('bur.status', 'active')
            ->where('r.is_staff', true)->whereIn('bur.user_id', $data['user_ids'])
            ->pluck('bur.user_id')->unique()->map(fn ($id) => (int) $id)->values();
        abort_if($validIds->count() !== collect($data['user_ids'])->unique()->count(), 422, 'فقط پرسنل فعال همین ساختمان قابل ارجاع هستند.');

        DB::transaction(function () use ($item, $user, $validIds, $buildingId) {
            foreach ($validIds as $staffId) {
                $assignee = TicketAssignee::where('ticket_id', $item->id)->where('user_id', $staffId)->first();
                if (! $assignee) {
                    TicketAssignee::create([
                        'building_id' => $buildingId, 'ticket_id' => $item->id,
                        'user_id' => $staffId, 'assigned_by' => $user->id, 'assigned_at' => now(),
                    ]);
                }
            }
            TicketEvent::create([
                'building_id' => $buildingId, 'ticket_id' => $item->id,
                'user_id' => $user->id, 'type' => 'assignment_changed',
                'description' => 'همکار یا همکاران جدید به تسک اضافه شدند.',
                'metadata' => ['added_user_ids' => $validIds->all()],
                'created_at' => now(),
            ]);
        });

        foreach (User::whereIn('id', $validIds)->get() as $recipient) {
            $recipient->notify(new RequestAlertNotification(
                buildingId: $buildingId,
                ticketId: (int) $item->id,
                event: 'task_assigned',
                title: 'به‌عنوان همکار به تسک اضافه شدید',
                message: 'شما به تسک «'.$item->title.'» اضافه شده‌اید.',
                url: route('tickets.show', $item->id),
            ));
        }

        return back()->with('status', 'همکاران تسک به‌روزرسانی شدند.');
    }

    public function rate(Request $request, int $ticket)
    {
        $item = $this->visibleTask($request, $ticket);
        $user = $request->user();
        abort_unless((int) $item->created_by === (int) $user->id, 403);
        abort_unless($item->status->value === 'closed', 422, 'امتیازدهی پس از تأیید نهایی مدیر امکان‌پذیر است.');

        $data = $request->validate([
            'ratings' => ['required', 'array', 'min:1'],
            'ratings.*.user_id' => ['required', 'integer', 'distinct'],
            'ratings.*.score' => ['required', 'integer', 'min:1', 'max:5'],
            'ratings.*.comment' => ['nullable', 'string', 'max:1000'],
        ]);
        $assigneeIds = $item->assignees->pluck('user_id')->map(fn ($id) => (int) $id);
        foreach ($data['ratings'] as $rating) {
            $ratedUserId = (int) $rating['user_id'];
            abort_unless($assigneeIds->contains($ratedUserId) && $ratedUserId !== (int) $user->id, 422);
            TicketRating::updateOrCreate(
                ['ticket_id' => $item->id, 'rated_user_id' => $ratedUserId],
                ['building_id' => $item->building_id, 'rated_by' => $user->id, 'score' => $rating['score'], 'comment' => $rating['comment'] ?? null],
            );
        }

        TicketEvent::create([
            'building_id' => $item->building_id, 'ticket_id' => $item->id,
            'user_id' => $user->id, 'type' => 'rated',
            'description' => 'ارزیابی عملکرد پرسنل پس از پایان تسک ثبت شد.',
            'metadata' => ['count' => count($data['ratings'])],
            'created_at' => now(),
        ]);

        return back()->with('status', 'امتیاز عملکرد پرسنل ثبت شد.');
    }

    public function attachment(Request $request, int $ticket, int $attachment)
    {
        $item = $this->visibleTask($request, $ticket);
        $file = $item->attachments()->findOrFail($attachment);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        return Storage::disk('local')->download($file->path, $file->original_name, [
            'Content-Type' => $file->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function visibleTask(Request $request, int $id): Ticket
    {
        $item = Ticket::query()->where('kind', TicketKind::Task->value)
            ->with(['createdBy', 'unit.block', 'acceptedBy', 'assignees.user', 'comments.user', 'events.user', 'statusLogs.user', 'ratings.ratedUser', 'attachments'])
            ->findOrFail($id);
        $user = $request->user();
        $assigned = $item->assignees->contains(fn ($a) => (int) $a->user_id === (int) $user->id);
        abort_unless(
            $user->hasPermission('ticket.view.all')
            || ((int) $item->created_by === (int) $user->id)
            || ($user->hasPermission('ticket.view.assigned') && $assigned),
            403
        );

        return $item;
    }

    private function roleOptions()
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $roles = Role::query()->where('building_id', $buildingId)->where('is_staff', true)->where('is_active', true)->orderBy('name_fa')->get();
        return $roles->isNotEmpty() ? $roles : Role::query()->whereNull('building_id')->where('is_staff', true)->whereIn('key', ['security', 'cleaning', 'maintenance'])->orderBy('name_fa')->get();
    }

    private function staffOptions()
    {
        $buildingId = app(CurrentBuilding::class)->id();
        return User::query()->select('users.id', 'users.first_name', 'users.last_name', 'users.company_name', 'users.person_type', 'users.mobile')
            ->join('building_user_roles as bur', 'bur.user_id', '=', 'users.id')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.building_id', $buildingId)->where('bur.status', 'active')
            ->where('r.is_staff', true)->whereNull('users.deleted_at')
            ->distinct()->orderBy('users.first_name')->get();
    }
}
