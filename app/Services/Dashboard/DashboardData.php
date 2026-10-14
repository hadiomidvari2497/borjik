<?php

namespace App\Services\Dashboard;

use App\Models\Building;
use App\Models\ChargePeriod;
use App\Models\ChargeSetting;
use App\Models\Event;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\Ticket;
use App\Models\Unit;
use App\Models\UnitParty;
use App\Models\User;
use App\Services\SubscriptionLimit;
use App\Support\CurrentBuilding;
use Morilog\Jalali\Jalalian;

/** داده‌ی داشبورد برای سه گروه نقش. همه‌ی کوئری‌ها به ساختمان فعال محدودند (BuildingScope). */
class DashboardData
{
    private const CLOSED = ['done', 'closed'];

    /** مالک و مدیر. */
    public function management(): array
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $building = Building::with('subscription')->find($buildingId);
        $sub = $building?->subscription;

        $byStatus = ['sold' => 0, 'rented' => 0, 'vacant' => 0, 'repair' => 0];
        $residents = 0;
        foreach (Unit::selectRaw('status, count(*) as c, coalesce(sum(residents_count), 0) as r')->groupBy('status')->get() as $row) {
            $byStatus[$row->status->value] = (int) $row->c;
            $residents += (int) $row->r;
        }
        $totalUnits = array_sum($byStatus);

        $open = Invoice::whereIn('status', ['unpaid', 'partial']);
        $overdue = (clone $open)->whereDate('due_date', '<', today())
            ->selectRaw('count(*) as c, coalesce(sum(amount - paid_amount), 0) as s')->first();

        $tickets = $this->ticketCounts();
        $collections = $this->collections();

        return [
            'units' => ['total' => $totalUnits, 'byStatus' => $byStatus, 'residents' => $residents],
            'subscription' => [
                'limit' => $sub?->units_limit,
                'used' => $sub ? app(SubscriptionLimit::class)->used($sub) : $totalUnits,
                'expires' => $sub?->expires_at,
            ],
            'overdue' => ['count' => (int) $overdue->c, 'amount' => (int) $overdue->s, 'openInvoices' => (clone $open)->count()],
            'tickets' => $tickets,
            'period' => $this->currentPeriod(),
            'events' => $this->upcomingEvents(),
            'collections' => $collections,
            'pendingReceipts' => PaymentReceipt::where('status', 'pending')->count(),
        ];
    }

    /** حراست، نظافت و تعمیرات. */
    public function staff(User $user): array
    {
        $tasks = Ticket::whereHas('assignees', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotIn('status', self::CLOSED)
            ->orderByRaw("case priority when 'urgent' then 0 when 'high' then 1 when 'medium' then 2 else 3 end")
            ->orderBy('deadline')
            ->limit(8)->get();

        return ['tasks' => $tasks, 'events' => $this->upcomingEvents()];
    }

    /** مالک واحد و مستأجر. */
    public function resident(User $user): array
    {
        $unitIds = UnitParty::where('user_id', $user->id)->whereNull('to_date')->pluck('unit_id');
        $units = Unit::with('block')->whereIn('id', $unitIds)->get();

        $unpaid = Invoice::where('payer_user_id', $user->id)->whereIn('status', ['unpaid', 'partial'])
            ->orderBy('due_date')->get();

        return [
            'units' => $units,
            'unpaid' => $unpaid,
            'debt' => (int) $unpaid->sum(fn (Invoice $i) => $i->amount - $i->paid_amount),
            'overdueCount' => $unpaid->filter(fn (Invoice $i) => $i->due_date->isPast())->count(),
            'openRequests' => Ticket::where('kind', 'request')->where('created_by', $user->id)->whereNotIn('status', self::CLOSED)->count(),
            'events' => $this->upcomingEvents(),
        ];
    }

    // ---------- بخش‌های مشترک

    private function ticketCounts(): array
    {
        $byStatus = array_fill_keys(['new', 'review', 'doing', 'waiting', 'done', 'closed'], 0);
        $openRequests = $openTasks = $urgent = $allRequests = 0;

        foreach (Ticket::selectRaw('status, kind, priority, count(*) as c')->groupBy('status', 'kind', 'priority')->get() as $row) {
            $status = $row->status->value;
            $c = (int) $row->c;
            $byStatus[$status] += $c;
            $isOpen = ! in_array($status, self::CLOSED, true);

            if ($row->kind->value === 'request') {
                $allRequests += $c;
                $openRequests += $isOpen ? $c : 0;
            } else {
                $openTasks += $isOpen ? $c : 0;
            }
            $urgent += ($isOpen && $row->priority->value === 'urgent') ? $c : 0;
        }

        return compact('byStatus', 'openRequests', 'openTasks', 'urgent', 'allRequests') + ['total' => array_sum($byStatus)];
    }

    /** وضعیت شارژ ماه جاری شمسی: دوره، تاریخ محاسبه، ثبت هزینه‌ها و روزهای مانده تا هشدار. */
    private function currentPeriod(): array
    {
        $now = Jalalian::now();
        $setting = ChargeSetting::first();
        $period = ChargePeriod::where('jalali_year', $now->getYear())->where('jalali_month', $now->getMonth())->first();

        $calcDay = min($setting?->calc_day ?? 25, $now->getMonthDays());
        $calc = (new Jalalian($now->getYear(), $now->getMonth(), $calcDay))->toCarbon()->startOfDay();

        return [
            'year' => $now->getYear(),
            'month' => $now->getMonth(),
            'status' => $period?->status->value,
            'calcDate' => $calc,
            'daysLeft' => (int) today()->diffInDays($calc, false),
            'warnFrom' => $calc->copy()->subDays($setting?->warn_days_before ?? 5),
            'method' => $setting?->method,
            'expensesCount' => $period ? Expense::where('period_id', $period->id)->count() : 0,
            'configured' => (bool) $setting,
        ];
    }

    private function upcomingEvents()
    {
        return Event::where('starts_at', '>=', now())->orderBy('starts_at')->limit(3)->get();
    }

    /** مجموع فیش‌های تأییدشده در ۱۲ ماه شمسی اخیر. @return array{labels: string[], values: int[]} */
    private function collections(): array
    {
        $now = Jalalian::now();
        $end = $now->getYear() * 12 + $now->getMonth() - 1;
        $startIdx = $end - 11;
        $from = (new Jalalian(intdiv($startIdx, 12), $startIdx % 12 + 1, 1))->toCarbon()->startOfDay();

        $sums = array_fill($startIdx, 12, 0);
        foreach (PaymentReceipt::where('status', 'approved')->where('paid_at', '>=', $from)->get(['amount', 'paid_at']) as $r) {
            $j = Jalalian::fromCarbon($r->paid_at);
            $idx = $j->getYear() * 12 + $j->getMonth() - 1;
            if (isset($sums[$idx])) {
                $sums[$idx] += (int) $r->amount;
            }
        }

        $labels = [];
        foreach (array_keys($sums) as $idx) {
            $labels[] = \App\Support\Fa::MONTHS[$idx % 12];
        }

        return ['labels' => $labels, 'values' => array_values($sums)];
    }
}
