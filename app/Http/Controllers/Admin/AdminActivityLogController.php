<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:160'],
            'admin_id' => ['nullable', 'integer', 'exists:admin_users,id'],
            'method' => ['nullable', Rule::in(['POST', 'PUT', 'PATCH', 'DELETE'])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $q = trim((string) ($filters['q'] ?? ''));
        $logs = AdminActivityLog::query()
            ->with('admin:id,name,username')
            ->when($q !== '', fn ($query) => $query->where(function ($search) use ($q) {
                $like = '%'.addcslashes($q, '%_\\\\').'%';
                $search->where('action', 'like', $like)
                    ->orWhere('path', 'like', $like)
                    ->orWhere('route_name', 'like', $like)
                    ->orWhere('ip_address', 'like', $like)
                    ->orWhereHas('admin', fn ($admin) => $admin->where('name', 'like', $like)->orWhere('username', 'like', $like));
            }))
            ->when(! empty($filters['admin_id']), fn ($query) => $query->where('admin_user_id', $filters['admin_id']))
            ->when(! empty($filters['method']), fn ($query) => $query->where('method', $filters['method']))
            ->when(! empty($filters['from']), fn ($query) => $query->where('created_at', '>=', $filters['from'].' 00:00:00'))
            ->when(! empty($filters['to']), fn ($query) => $query->where('created_at', '<=', $filters['to'].' 23:59:59'))
            ->orderByDesc('created_at');

        return view('admin.activity-logs.index', [
            'logs' => $logs->paginate(25)->withQueryString(),
            'admins' => AdminUser::query()->orderBy('username')->get(['id', 'name', 'username']),
            'filters' => array_merge(['q' => '', 'admin_id' => '', 'method' => '', 'from' => '', 'to' => ''], $filters),
            'stats' => [
                'today' => AdminActivityLog::whereDate('created_at', today())->count(),
                'week' => AdminActivityLog::where('created_at', '>=', now()->subDays(7))->count(),
                'admins' => AdminActivityLog::whereNotNull('admin_user_id')->distinct('admin_user_id')->count('admin_user_id'),
            ],
        ]);
    }
    public function clear(Request $request)
    {
        $data = $request->validate([
            'scope' => ['required', 'in:all,range'],
            'from' => ['required_if:scope,range', 'nullable', 'date_format:Y-m-d'],
            'to' => ['required_if:scope,range', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'confirmation' => ['required', 'in:حذف'],
        ]);

        $query = AdminActivityLog::query();
        if ($data['scope'] === 'range') {
            $query->whereBetween('created_at', [$data['from'].' 00:00:00', $data['to'].' 23:59:59']);
        }

        $deleted = $query->delete();

        return redirect()->route('admin.activity-logs.index')
            ->with('status', number_format($deleted).' رکورد از تاریخچه فعالیت مدیران پاک شد.');
    }
}
