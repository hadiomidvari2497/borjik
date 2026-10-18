<?php

namespace App\Http\Controllers;

use App\Enums\TicketKind;
use App\Models\CalendarEvent;
use App\Models\Poll;
use App\Models\Ticket;
use App\Support\CurrentBuilding;
use App\Support\Fa;
use App\Support\JalaliDate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Morilog\Jalali\Jalalian;

class CalendarController extends Controller
{
    private const MONTHS = [
        1 => 'فروردین', 2 => 'اردیبهشت', 3 => 'خرداد', 4 => 'تیر',
        5 => 'مرداد', 6 => 'شهریور', 7 => 'مهر', 8 => 'آبان',
        9 => 'آذر', 10 => 'دی', 11 => 'بهمن', 12 => 'اسفند',
    ];

    public function index(Request $request, CurrentBuilding $current): View
    {
        abort_unless($current->id(), 403);
        $user = $request->user();
        abort_unless($user->hasPermission('event.view') || $user->hasPermission('event.manage'), 403);

        $viewMode = (string) $request->query('view', 'month');
        if (! in_array($viewMode, ['month', 'week', 'day'], true)) {
            $viewMode = 'month';
        }
        $monthInput = (string) $request->query('month', Jalalian::fromCarbon(now())->format('Y/m'));
        $dateInput = (string) $request->query('date', now()->format('Y/m/d'));
        $monthLatin = str_replace(['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'], range(0, 9), $monthInput);
        $parsedMonth = preg_match('#^(\\d{4})/(\\d{1,2})$#', $monthLatin, $match)
            ? JalaliDate::parse(sprintf('%04d/%02d/01', (int) $match[1], (int) $match[2]))
            : null;
        $parsedDate = JalaliDate::parse($dateInput);
        $anchor = ($parsedDate ?? $parsedMonth ?? now())->startOfDay();
        $jalali = Jalalian::fromCarbon($anchor);
        $year = $jalali->getYear();
        $month = $jalali->getMonth();
        $jalaliStart = new Jalalian($year, $month, 1);
        $monthStart = $jalaliStart->toCarbon()->startOfDay();
        $daysCount = $jalaliStart->getMonthDays();
        $monthKey = sprintf('%04d/%02d', $year, $month);
        $prevKey = sprintf('%04d/%02d', $month === 1 ? $year - 1 : $year, $month === 1 ? 12 : $month - 1);
        $nextKey = sprintf('%04d/%02d', $month === 12 ? $year + 1 : $year, $month === 12 ? 1 : $month + 1);

        if ($viewMode === 'week') {
            $daysSinceSaturday = ((int) $anchor->dayOfWeek + 1) % 7;
            $start = $anchor->copy()->subDays($daysSinceSaturday)->startOfDay();
            $rangeDays = 7;
        } elseif ($viewMode === 'day') {
            $start = $anchor->copy()->startOfDay();
            $rangeDays = 1;
        } else {
            $start = $monthStart;
            $rangeDays = $daysCount;
        }
        $end = $start->copy()->addDays($rangeDays);
        $anchorKey = JalaliDate::format($anchor);
        $prevDateKey = JalaliDate::format($viewMode === 'week' ? $start->copy()->subWeek() : ($viewMode === 'day' ? $start->copy()->subDay() : $monthStart));
        $nextDateKey = JalaliDate::format($viewMode === 'week' ? $start->copy()->addWeek() : ($viewMode === 'day' ? $start->copy()->addDay() : $monthStart));

        $filter = (string) $request->query('filter', 'all');
        if (! in_array($filter, ['all', 'events', 'tasks', 'polls'], true)) { $filter = 'all'; }

        $items = collect();
        $customEvents = CalendarEvent::query()
            ->where('starts_at', '<', $end)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', $start))
            ->with('creator')
            ->orderBy('starts_at')
            ->get();

        foreach ($customEvents as $event) {
            $items->push([
                'id' => 'event-'.$event->id,
                'title' => $event->title,
                'description' => $event->description,
                'date' => $event->starts_at->toDateString(),
                'time' => $event->is_all_day ? 'تمام روز' : Fa::digits($event->starts_at->format('H:i')),
                'color' => $event->color,
                'type' => 'رویداد',
                'url' => null,
                'starts_at' => $event->starts_at,
                'managed' => $user->hasPermission('event.manage'),
                'event_id' => $event->id,
                'reminder_minutes' => $event->reminder_minutes,
                'event_model' => $event,
            ]);
        }

        $tasks = Ticket::query()->where('kind', TicketKind::Task->value)
            ->whereNotIn('status', ['done', 'closed'])
            ->whereNotNull('deadline')->whereBetween('deadline', [$start, $end])
            ->when(! $user->hasPermission('ticket.view.all'), fn ($query) => $query->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)->orWhereHas('assignees', fn ($a) => $a->where('user_id', $user->id));
            }))
            ->get(['id', 'title', 'deadline', 'priority']);
        foreach ($tasks as $task) {
            $items->push([
                'id' => 'task-'.$task->id, 'title' => $task->title, 'description' => null,
                'date' => $task->deadline->toDateString(), 'time' => Fa::digits($task->deadline->format('H:i')),
                'color' => 'amber', 'type' => 'مهلت تسک', 'url' => route('tickets.show', $task->id),
                'starts_at' => $task->deadline, 'managed' => false, 'event_id' => null, 'reminder_minutes' => null, 'event_model' => null,
            ]);
        }

        $polls = Poll::query()->where(function ($query) use ($start, $end) {
            $query->whereBetween('starts_at', [$start, $end])->orWhereBetween('ends_at', [$start, $end]);
        })->get(['id', 'title', 'starts_at', 'ends_at']);
        foreach ($polls as $poll) {
            foreach ([['date' => $poll->starts_at, 'type' => 'شروع نظرسنجی', 'color' => 'blue'], ['date' => $poll->ends_at, 'type' => 'پایان نظرسنجی', 'color' => 'rose']] as $index => $entry) {
                $date = $entry['date'];
                if ($date && $date->betweenIncluded($start, $end->copy()->subSecond())) {
                    $items->push([
                        'id' => 'poll-'.$poll->id.'-'.$index, 'title' => $poll->title, 'description' => null,
                        'date' => $date->toDateString(), 'time' => Fa::digits($date->format('H:i')),
                        'color' => $entry['color'], 'type' => $entry['type'],
                        'url' => $user->hasPermission('poll.manage') ? route('polls.show', $poll->id) : route('polls.index'),
                        'starts_at' => $date, 'managed' => false, 'event_id' => null, 'reminder_minutes' => null, 'event_model' => null,
                    ]);
                }
            }
        }

        $items = $items->filter(fn ($item) => $filter === 'all'
            || ($filter === 'events' && $item['type'] === 'رویداد')
            || ($filter === 'tasks' && $item['type'] === 'مهلت تسک')
            || ($filter === 'polls' && in_array($item['type'], ['شروع نظرسنجی', 'پایان نظرسنجی'], true)))->values();

        $byDate = $items->groupBy('date');
        $days = collect(range(1, $rangeDays))->map(function (int $day) use ($start, $byDate) {
            $date = $start->copy()->addDays($day - 1);
            return [
                'day' => $day,
                'date' => $date->toDateString(),
                'jalali' => Fa::digits((string) $day),
                'is_today' => $date->isToday(),
                'items' => ($byDate->get($date->toDateString()) ?? collect())->sortBy('starts_at')->values(),
            ];
        });
        $leading = $viewMode === 'month' ? (Carbon::parse($start)->dayOfWeek + 1) % 7 : 0;
        $upcoming = $items->filter(fn ($item) => $item['starts_at']->greaterThanOrEqualTo(now()->startOfDay()))
            ->sortBy('starts_at')->take(6)->values();

        return view('calendar.index', [
            'year' => $year, 'month' => $month, 'monthName' => self::MONTHS[$month],
            'monthKey' => $monthKey, 'prevKey' => $prevKey, 'nextKey' => $nextKey,
            'viewMode' => $viewMode, 'anchorKey' => $anchorKey, 'prevDateKey' => $prevDateKey, 'nextDateKey' => $nextDateKey,
            'days' => $days, 'leading' => $leading, 'items' => $items,
            'upcoming' => $upcoming, 'canManage' => $user->hasPermission('event.manage'),
            'todayLabel' => JalaliDate::format(now()),
            'monthNames' => self::MONTHS,
            'filter' => $filter,
        ]);
    }

    public function store(Request $request, CurrentBuilding $current): RedirectResponse
    {
        abort_unless($current->id() && $request->user()->hasPermission('event.manage'), 403);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'start_date' => ['required', 'string', 'max:10'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_date' => ['nullable', 'string', 'max:10'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'is_all_day' => ['nullable', 'boolean'],
            'color' => ['required', 'in:teal,blue,amber,rose,violet'],
            'reminder_minutes' => ['nullable', 'integer', 'in:0,15,60,1440,10080'],
        ]);

        $start = JalaliDate::parse($data['start_date']);
        $end = ! empty($data['end_date']) ? JalaliDate::parse($data['end_date']) : null;
        if (! $start || (! empty($data['end_date']) && ! $end)) {
            return back()->withErrors(['start_date' => 'تاریخ شمسی واردشده معتبر نیست.'])->withInput();
        }
        $allDay = $request->boolean('is_all_day');
        $start = $allDay ? $start->startOfDay() : $start->setTimeFromTimeString($data['start_time'] ?? '09:00');
        $end = $end ? ($allDay ? $end->endOfDay() : $end->setTimeFromTimeString($data['end_time'] ?? ($data['start_time'] ?? '10:00'))) : ($allDay ? $start->copy()->endOfDay() : $start->copy()->addHour());
        if ($end->lessThan($start)) {
            return back()->withErrors(['end_date' => 'زمان پایان نمی‌تواند قبل از شروع باشد.'])->withInput();
        }

        CalendarEvent::create([
            'created_by' => $request->user()->id,
            'title' => trim($data['title']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'starts_at' => $start,
            'ends_at' => $end,
            'is_all_day' => $allDay,
            'color' => $data['color'],
            'reminder_minutes' => isset($data['reminder_minutes']) && $data['reminder_minutes'] !== '' && (int) $data['reminder_minutes'] > 0 ? (int) $data['reminder_minutes'] : null,
            'reminder_sent_at' => null,
        ]);

        return redirect()->route('events.index', ['month' => Jalalian::fromCarbon($start)->format('Y/m')])
            ->with('status', 'رویداد با موفقیت به تقویم اضافه شد.');
    }


    public function update(Request $request, CurrentBuilding $current, int $event): RedirectResponse
    {
        abort_unless($current->id() && $request->user()->hasPermission('event.manage'), 403);
        $item = CalendarEvent::query()->findOrFail($event);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'start_date' => ['required', 'string', 'max:10'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_date' => ['nullable', 'string', 'max:10'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'is_all_day' => ['nullable', 'boolean'],
            'color' => ['required', 'in:teal,blue,amber,rose,violet'],
            'reminder_minutes' => ['nullable', 'integer', 'in:0,15,60,1440,10080'],
        ]);
        $start = JalaliDate::parse($data['start_date']);
        $end = ! empty($data['end_date']) ? JalaliDate::parse($data['end_date']) : null;
        if (! $start || (! empty($data['end_date']) && ! $end)) {
            return back()->withErrors(['start_date' => 'تاریخ شمسی واردشده معتبر نیست.'])->withInput();
        }
        $allDay = $request->boolean('is_all_day');
        $start = $allDay ? $start->startOfDay() : $start->setTimeFromTimeString($data['start_time'] ?? '09:00');
        $end = $end ? ($allDay ? $end->endOfDay() : $end->setTimeFromTimeString($data['end_time'] ?? ($data['start_time'] ?? '10:00'))) : ($allDay ? $start->copy()->endOfDay() : $start->copy()->addHour());
        if ($end->lessThan($start)) {
            return back()->withErrors(['end_date' => 'زمان پایان نمی‌تواند قبل از شروع باشد.'])->withInput();
        }
        $item->fill([
            'title' => trim($data['title']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'starts_at' => $start,
            'ends_at' => $end,
            'is_all_day' => $allDay,
            'color' => $data['color'],
            'reminder_minutes' => isset($data['reminder_minutes']) && (int) $data['reminder_minutes'] > 0 ? (int) $data['reminder_minutes'] : null,
            'reminder_sent_at' => null,
        ])->save();

        return redirect()->route('events.index', ['month' => Jalalian::fromCarbon($start)->format('Y/m')])
            ->with('status', 'رویداد با موفقیت ویرایش شد.');
    }

    public function destroy(Request $request, int $event): RedirectResponse
    {
        abort_unless($request->user()->hasPermission('event.manage'), 403);
        $item = CalendarEvent::query()->findOrFail($event);
        $month = Jalalian::fromCarbon($item->starts_at)->format('Y/m');
        $item->delete();

        return redirect()->route('events.index', ['month' => $month])->with('status', 'رویداد از تقویم حذف شد.');
    }
}
