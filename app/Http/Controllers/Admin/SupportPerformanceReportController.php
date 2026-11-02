<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\JalaliDate;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupportPerformanceReportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'string', 'max:20'],
            'to' => ['nullable', 'string', 'max:20'],
        ]);

        $from = $request->filled('from')
            ? JalaliDate::parse($validated['from'])
            : now()->subDays(29)->startOfDay();
        $to = $request->filled('to')
            ? JalaliDate::parse($validated['to'])?->endOfDay()
            : now()->endOfDay();

        if ($request->filled('from') && ! $from) {
            throw ValidationException::withMessages(['from' => 'تاریخ شروع شمسی معتبر نیست.']);
        }

        if ($request->filled('to') && ! $to) {
            throw ValidationException::withMessages(['to' => 'تاریخ پایان شمسی معتبر نیست.']);
        }

        if ($from->gt($to)) {
            throw ValidationException::withMessages(['to' => 'تاریخ پایان نباید قبل از تاریخ شروع باشد.']);
        }

        $summary = $this->metricsQuery($from, $to)->first();
        $agents = $this->metricsQuery($from, $to, true)
            ->orderByDesc('tickets')
            ->orderBy('agent_name')
            ->get()
            ->map(fn ($row) => $this->presentMetrics($row));

        return view('admin.support.reports', [
            'summary' => $this->presentMetrics($summary),
            'agents' => $agents,
            'filters' => [
                'from' => $request->query('from', JalaliDate::format($from)),
                'to' => $request->query('to', JalaliDate::format($to)),
            ],
        ]);
    }

    private function metricsQuery(Carbon $from, Carbon $to, bool $grouped = false): Builder
    {
        $driver = DB::connection()->getDriverName();
        $responseMinutes = $driver === 'sqlite'
            ? '(julianday(t.first_response_at) - julianday(t.created_at)) * 1440'
            : 'TIMESTAMPDIFF(SECOND, t.created_at, t.first_response_at) / 60';
        $resolutionMinutes = $driver === 'sqlite'
            ? '(julianday(t.closed_at) - julianday(t.created_at)) * 1440'
            : 'TIMESTAMPDIFF(SECOND, t.created_at, t.closed_at) / 60';

        $query = DB::table('support_tickets as t')
            ->leftJoin('admin_users as a', 'a.id', '=', 't.assigned_admin_id')
            ->whereBetween('t.created_at', [$from, $to])
            ->selectRaw("
                COUNT(*) AS tickets,
                SUM(CASE WHEN t.status IN ('resolved', 'closed') THEN 1 ELSE 0 END) AS resolved_tickets,
                SUM(CASE WHEN t.first_response_at IS NOT NULL THEN 1 ELSE 0 END) AS responded_tickets,
                AVG(CASE WHEN t.first_response_at IS NOT NULL THEN {$responseMinutes} END) AS avg_first_response_minutes,
                AVG(CASE WHEN t.closed_at IS NOT NULL THEN {$resolutionMinutes} END) AS avg_resolution_minutes,
                SUM(CASE WHEN t.first_response_at IS NOT NULL OR t.first_response_breached_at IS NOT NULL THEN 1 ELSE 0 END) AS response_sla_total,
                SUM(CASE WHEN t.first_response_at IS NOT NULL AND t.first_response_breached_at IS NULL THEN 1 ELSE 0 END) AS response_sla_met,
                SUM(CASE WHEN t.closed_at IS NOT NULL OR t.resolution_breached_at IS NOT NULL THEN 1 ELSE 0 END) AS resolution_sla_total,
                SUM(CASE WHEN t.closed_at IS NOT NULL AND t.resolution_breached_at IS NULL THEN 1 ELSE 0 END) AS resolution_sla_met
            ");

        if ($grouped) {
            $query->addSelect(DB::raw("t.assigned_admin_id AS agent_id"))
                ->addSelect(DB::raw("COALESCE(a.name, 'بدون کارشناس') AS agent_name"))
                ->groupBy('t.assigned_admin_id', 'a.name');
        }

        return $query;
    }

    private function presentMetrics(object $row): array
    {
        $responseTotal = (int) ($row->response_sla_total ?? 0);
        $resolutionTotal = (int) ($row->resolution_sla_total ?? 0);
        $slaTotal = $responseTotal + $resolutionTotal;
        $slaMet = (int) ($row->response_sla_met ?? 0) + (int) ($row->resolution_sla_met ?? 0);

        return [
            'agent_id' => $row->agent_id ?? null,
            'agent_name' => $row->agent_name ?? null,
            'tickets' => (int) ($row->tickets ?? 0),
            'responded_tickets' => (int) ($row->responded_tickets ?? 0),
            'resolved_tickets' => (int) ($row->resolved_tickets ?? 0),
            'avg_first_response_minutes' => $row->avg_first_response_minutes !== null ? (float) $row->avg_first_response_minutes : null,
            'avg_resolution_minutes' => $row->avg_resolution_minutes !== null ? (float) $row->avg_resolution_minutes : null,
            'sla_compliance' => $slaTotal > 0 ? round(($slaMet / $slaTotal) * 100, 1) : null,
            'response_sla_compliance' => $responseTotal > 0 ? round(((int) $row->response_sla_met / $responseTotal) * 100, 1) : null,
            'resolution_sla_compliance' => $resolutionTotal > 0 ? round(((int) $row->resolution_sla_met / $resolutionTotal) * 100, 1) : null,
        ];
    }
}
