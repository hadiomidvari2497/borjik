<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Role;
use App\Models\Ticket;
use App\Notifications\RequestAlertNotification;
use App\Models\TicketAssignee;
use App\Models\TicketComment;
use App\Models\TicketEvent;
use App\Models\TicketStatusLog;
use App\Models\Unit;
use App\Models\User;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $baseQuery = Ticket::where('kind', 'request')->with(['createdBy', 'unit', 'assignees.user', 'acceptedBy'])->latest();
        if (! $user->hasPermission('request.view.all')) {
            $baseQuery->whereHas('assignees', fn ($q) => $q->where('user_id', $user->id));
        }

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_keys($this->statusLabels()))],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'category' => ['nullable', Rule::in(array_keys(self::CATEGORIES))],
        ]);

        $stats = ['total' => (clone $baseQuery)->count()];
        foreach (array_keys($this->statusLabels()) as $status) {
            $stats[$status] = (clone $baseQuery)->where('status', $status)->count();
        }
        $stats['resolved'] = $stats['done'] + $stats['closed'];
        $stats['open'] = max(0, $stats['total'] - $stats['resolved']);
        $stats['overdue'] = (clone $baseQuery)->whereNotIn('status', ['done', 'closed'])
            ->whereNotNull('deadline')->where('deadline', '<', now())->count();
        $stats['resolution_rate'] = $stats['total'] > 0
            ? (int) round(($stats['resolved'] / $stats['total']) * 100)
            : 0;

        $reportFilters = $request->validate([
            'report_from' => ['nullable', 'date', 'before_or_equal:report_to'],
            'report_to' => ['nullable', 'date', 'after_or_equal:report_from'],
        ]);
        $reportFrom = ! empty($reportFilters['report_from'])
            ? now()->parse($reportFilters['report_from'])->startOfMonth()
            : now()->subMonths(5)->startOfMonth();
        $reportTo = ! empty($reportFilters['report_to'])
            ? now()->parse($reportFilters['report_to'])->endOfMonth()
            : now()->endOfMonth();

        $visibleTicketIds = (clone $baseQuery)->reorder()->pluck('tickets.id');
        $ticketCreatedAt = Ticket::whereIn('id', $visibleTicketIds)->pluck('created_at', 'id');
        $completionLogs = TicketStatusLog::query()
            ->whereIn('ticket_id', $visibleTicketIds)
            ->whereIn('to_status', ['done', 'closed'])
            ->whereBetween('created_at', [$reportFrom, $reportTo])
            ->orderBy('created_at')
            ->get(['ticket_id', 'created_at', 'to_status'])
            ->groupBy('ticket_id')
            ->map(fn ($logs) => $logs->first());

        $reportRows = collect();
        $cursor = $reportFrom->copy()->startOfMonth();
        while ($cursor->lte($reportTo)) {
            $monthStart = $cursor->copy()->startOfMonth();
            $monthEnd = $cursor->copy()->endOfMonth();
            $createdCount = (clone $baseQuery)->whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $resolvedLogs = $completionLogs->filter(fn ($log) => $log->created_at >= $monthStart && $log->created_at <= $monthEnd);
            $durations = $resolvedLogs->map(function ($log) use ($ticketCreatedAt) {
                $createdAt = $ticketCreatedAt->get($log->ticket_id);
                return $createdAt ? max(0, \Illuminate\Support\Carbon::parse($createdAt)->diffInMinutes($log->created_at, false)) : null;
            })->filter(fn ($minutes) => $minutes !== null);
            $reportRows->push([
                'month' => $monthStart->format('Y/m'),
                'created' => $createdCount,
                'resolved' => $resolvedLogs->count(),
                'average_minutes' => $durations->isNotEmpty() ? (int) round($durations->avg()) : null,
            ]);
            $cursor->addMonth();
        }

        $query = clone $baseQuery;
        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('number', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhere('location', 'like', "%{$term}%")
                    ->orWhereHas('createdBy', fn ($creator) => $creator
                        ->where(function ($name) use ($term) {
                            $name->where('first_name', 'like', "%{$term}%")
                                ->orWhere('last_name', 'like', "%{$term}%")
                                ->orWhere('company_name', 'like', "%{$term}%");
                        })
                        ->orWhere('mobile', 'like', "%{$term}%"));
            });
        }
        $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['priority'] ?? null, fn ($q, $priority) => $q->where('priority', $priority))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category));

        return view('requests.index', [
            'tickets' => $query->paginate(15)->withQueryString(),
            'canManage' => $user->hasPermission('request.view.all'),
            'statuses' => $this->statusLabels(),
            'categories' => self::CATEGORIES,
            'stats' => $stats,
            'filters' => $filters,
            'reportRows' => $reportRows,
            'reportFrom' => $reportFrom,
            'reportTo' => $reportTo,
            'reportFilters' => $reportFilters,
        ]);
    }

    public function reports(Request $request)
    {
        $user = $request->user();
        abort_unless($user->hasPermission('request.view.all'), 403);

        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = isset($filters['from']) ? Carbon::parse($filters['from'])->startOfDay() : now()->startOfMonth()->startOfDay();
        $to = isset($filters['to']) ? Carbon::parse($filters['to'])->endOfDay() : now()->endOfDay();
        if ($from->diffInDays($to) > 365) {
            return back()->withErrors(['to' => 'بازه گزارش نمی‌تواند بیشتر از یک سال باشد.'])->withInput();
        }

        $buildingId = app(CurrentBuilding::class)->id();
        abort_unless($buildingId, 403);

        $createdQuery = Ticket::query()->where('building_id', $buildingId)
            ->where('kind', 'request')->whereBetween('created_at', [$from, $to]);
        $created = (clone $createdQuery)->count();

        $resolvedLogs = DB::table('ticket_status_logs as tsl')
            ->join('tickets as t', 't.id', '=', 'tsl.ticket_id')
            ->where('tsl.building_id', $buildingId)->where('t.building_id', $buildingId)
            ->where('t.kind', 'request')->whereNull('t.deleted_at')
            ->whereIn('tsl.to_status', ['done', 'closed'])
            ->whereBetween('tsl.created_at', [$from, $to])
            ->select('tsl.ticket_id', DB::raw('MIN(tsl.created_at) as resolved_at'))
            ->groupBy('tsl.ticket_id')->get();

        $resolved = $resolvedLogs->count();
        $durations = collect();
        if ($resolvedLogs->isNotEmpty()) {
            $ticketCreatedAt = Ticket::query()->where('building_id', $buildingId)
                ->where('kind', 'request')->whereIn('id', $resolvedLogs->pluck('ticket_id'))
                ->pluck('created_at', 'id');
            foreach ($resolvedLogs as $log) {
                if (isset($ticketCreatedAt[$log->ticket_id]) && $ticketCreatedAt[$log->ticket_id] !== null) {
                    $seconds = Carbon::parse($ticketCreatedAt[$log->ticket_id])->diffInSeconds(Carbon::parse($log->resolved_at), false);
                    if ($seconds >= 0) $durations->push($seconds);
                }
            }
        }
        $averageSeconds = $durations->isNotEmpty() ? (int) round($durations->avg()) : null;

        $days = [];
        $cursor = $from->copy()->startOfDay();
        $lastDay = $to->copy()->startOfDay();
        while ($cursor->lte($lastDay)) {
            $key = $cursor->toDateString();
            $days[$key] = ['date' => $key, 'created' => 0, 'resolved' => 0];
            $cursor->addDay();
        }

        $createdByDay = (clone $createdQuery)->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupByRaw('DATE(created_at)')->pluck('total', 'day');
        foreach ($createdByDay as $day => $count) {
            if (isset($days[$day])) $days[$day]['created'] = (int) $count;
        }

        $resolvedByDay = DB::table('ticket_status_logs as tsl')
            ->join('tickets as t', 't.id', '=', 'tsl.ticket_id')
            ->where('tsl.building_id', $buildingId)->where('t.building_id', $buildingId)
            ->where('t.kind', 'request')->whereNull('t.deleted_at')
            ->whereIn('tsl.to_status', ['done', 'closed'])
            ->whereBetween('tsl.created_at', [$from, $to])
            ->selectRaw('DATE(tsl.created_at) as day, COUNT(DISTINCT tsl.ticket_id) as total')
            ->groupByRaw('DATE(tsl.created_at)')->pluck('total', 'day');
        foreach ($resolvedByDay as $day => $count) {
            if (isset($days[$day])) $days[$day]['resolved'] = (int) $count;
        }

        return view('requests.reports', [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'stats' => ['created' => $created, 'resolved' => $resolved, 'average_seconds' => $averageSeconds],
            'chart' => array_values($days),
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

        $baseQuery = Ticket::where('kind', 'request')->where('created_by', $user->id);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(array_keys($this->statusLabels()))],
        ]);
        $stats = ['total' => (clone $baseQuery)->count()];
        foreach (array_keys($this->statusLabels()) as $status) {
            $stats[$status] = (clone $baseQuery)->where('status', $status)->count();
        }

        $query = (clone $baseQuery)->with(['unit', 'assignees.user', 'acceptedBy'])->latest();
        if (! empty($filters['q'])) {
            $term = trim($filters['q']);
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                    ->orWhere('number', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }
        $query->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));

        return view('my.requests', [
            'tickets' => $query->paginate(10)->withQueryString(),
            'categories' => self::CATEGORIES,
            'statuses' => $this->statusLabels(),
            'units' => Unit::query()
                ->when(! $user->hasPermission('request.view.all'), fn ($q) => $q->whereHas('parties', fn ($p) => $p->where('user_id', $user->id)->whereNull('to_date')))
                ->with('block')->orderBy('number')->get(),
            'stats' => $stats,
            'filters' => $filters,
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
                'deadline' => match ($data['priority']) {
                    'urgent' => now()->addHours(4),
                    'high' => now()->addHours(24),
                    'medium' => now()->addHours(72),
                    default => now()->addHours(168),
                },
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
        if ($previous === $status) {
            return;
        }

        $ticket->status = $status;
        $ticket->closed_at = $status === TicketStatus::Closed ? now() : null;
        $ticket->save();
        TicketStatusLog::create([
            'building_id' => app(CurrentBuilding::class)->id(), 'ticket_id' => $ticket->id,
            'from_status' => $previous?->value, 'to_status' => $status->value,
            'user_id' => $user->id, 'note' => $note, 'created_at' => now(),
        ]);
        $statusLabels = $this->statusLabels();
        $message = 'وضعیت درخواست «'.$ticket->title.'» از «'.($statusLabels[$previous?->value ?? ''] ?? '—').'» به «'.($statusLabels[$status->value] ?? $status->value).'» تغییر کرد.';
        $this->recordEvent($ticket, $user, 'status_changed', $message.($note ? ' '.$note : ''));

        $recipients = collect([$ticket->createdBy, $ticket->acceptedBy])
            ->filter()
            ->unique('id')
            ->reject(fn ($recipient) => (int) $recipient->id === (int) $user->id);

        foreach ($recipients as $recipient) {
            $url = (int) $recipient->id === (int) $ticket->created_by
                ? route('my.requests.show', $ticket->id)
                : route('requests.show', $ticket->id);
            $recipient->notify(new RequestAlertNotification(
                buildingId: (int) $ticket->building_id,
                ticketId: (int) $ticket->id,
                event: 'status_changed',
                title: 'تغییر وضعیت درخواست',
                message: $message,
                url: $url,
            ));
        }

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
