<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BuildingUserController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'status' => ['nullable', Rule::in(['active', 'disabled'])],
            'membership_status' => ['nullable', Rule::in(['active', 'invited', 'inactive', 'rejected'])],
        ]);
        $q = trim((string) ($filters['q'] ?? ''));
        $like = '%'.addcslashes($q, '%_\\').'%';

        $usersQuery = User::query()
            ->with(['buildingRoles.building:id,name', 'buildingRoles.role:id,name,key'])
            ->when($q !== '', fn ($query) => $query->where(function ($search) use ($like) {
                $search->where('mobile', 'like', $like)->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)->orWhere('company_name', 'like', $like)
                    ->orWhere('national_code', 'like', $like);
            }))
            ->when(! empty($filters['building_id']), fn ($query) => $query->whereHas('buildingRoles', fn ($m) => $m->where('building_id', $filters['building_id'])))
            ->when(! empty($filters['role_id']), fn ($query) => $query->whereHas('buildingRoles', fn ($m) => $m->where('role_id', $filters['role_id'])))
            ->when(! empty($filters['membership_status']), fn ($query) => $query->whereHas('buildingRoles', fn ($m) => $m->where('status', $filters['membership_status'])))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->orderByDesc('created_at');

        return view('admin.building-users.index', [
            'users' => $usersQuery->paginate(20)->withQueryString(),
            'filters' => array_merge(['q' => '', 'building_id' => '', 'role_id' => '', 'status' => '', 'membership_status' => ''], $filters),
            'buildings' => Building::query()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('status', 'active')->count(),
                'verified' => User::query()->whereNotNull('mobile_verified_at')->count(),
                'memberships' => DB::table('building_user_roles')->where('status', 'active')->distinct()->count('user_id'),
            ],
        ]);
    }
}
