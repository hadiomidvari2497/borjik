<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BuildingAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate(['period' => ['nullable', Rule::in(['7', '30', '90'])]]);
        $days = (int) ($filters['period'] ?? 30);
        $since = now()->subDays($days)->startOfDay();
        $buildings = Building::query()->withCount(['units', 'members as memberships_count' => fn ($query) => $query->where('status', 'active')])->orderBy('name')->get();
        $rows = $buildings->map(function (Building $building) use ($since) {
            $id = $building->getKey();
            $row = [
                'id' => $id,
                'name' => (string) ($building->name ?? ('ساختمان #'.$id)),
                'units' => (int) ($building->units_count ?? 0),
                'memberships' => (int) ($building->memberships_count ?? 0),
                'new_memberships' => $this->countByBuilding('building_user_roles', $id, $since),
                'tickets' => $this->countByBuilding('tickets', $id),
                'new_tickets' => $this->countByBuilding('tickets', $id, $since),
                'invoices' => $this->countByBuilding('invoices', $id),
                'new_invoices' => $this->countByBuilding('invoices', $id, $since),
                'receipts' => $this->countByBuilding('payment_receipts', $id),
                'pending_receipts' => $this->countByBuilding('payment_receipts', $id, null, 'pending'),
                'new_receipts' => $this->countByBuilding('payment_receipts', $id, $since),
            ];
            return $row;
        });

        $sum = fn (string $key) => (int) $rows->sum($key);
        return view('admin.building-analytics.index', [
            'days' => $days,
            'rows' => $rows,
            'stats' => [
                'buildings' => $buildings->count(),
                'units' => $sum('units'),
                'memberships' => $sum('memberships'),
                'tickets' => $sum('tickets'),
                'new_tickets' => $sum('new_tickets'),
                'invoices' => $sum('invoices'),
                'receipts' => $sum('receipts'),
                'pending_receipts' => $sum('pending_receipts'),
                'new_buildings' => Building::query()->where('created_at', '>=', $since)->count(),
                'new_users' => User::query()->where('created_at', '>=', $since)->count(),
            ],
            'periodStart' => $since,
        ]);
    }

    private function countByBuilding(string $table, int $buildingId, $since = null, ?string $status = null): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'building_id')) {
            return 0;
        }
        $query = DB::table($table)->where('building_id', $buildingId);
        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        if ($since && Schema::hasColumn($table, 'created_at')) {
            $query->where('created_at', '>=', $since);
        }
        if ($status !== null && Schema::hasColumn($table, 'status')) {
            $query->where('status', $status);
        }
        if ($table === 'building_user_roles' && Schema::hasColumn($table, 'status')) {
            $query->where('status', 'active');
        }
        return (int) $query->count();
    }
}
