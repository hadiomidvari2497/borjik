<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Role;
use App\Models\User;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BuildingUserController extends Controller
{
    public function show(int $user)
    {
        $customer = User::query()->with([
            'buildingRoles.building:id,name,code,created_by,subscription_id',
            'buildingRoles.role:id,name_fa,key',
        ])->findOrFail($user);

        $canViewFinance = auth('admin')->user()?->hasPermission('subscriptions.manage') ?? false;
        $subscriptions = $canViewFinance
            ? Subscription::query()->with(['plan:id,name,units_limit,duration_months', 'building:id,name,code'])
                ->where('user_id', $customer->id)->latest('id')->get()
            : collect();
        $payments = $canViewFinance
            ? SubscriptionPayment::query()->where('user_id', $customer->id)->latest('id')->limit(50)->get()
            : collect();

        return view('admin.building-users.show', [
            'customer' => $customer,
            'subscriptions' => $subscriptions,
            'payments' => $payments,
            'canViewFinance' => $canViewFinance,
            'paidTotal' => $canViewFinance ? (int) SubscriptionPayment::query()->where('user_id', $customer->id)->where('status', 'paid')->sum('amount') : null,
        ]);
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'building_id' => ['nullable', 'integer', 'exists:buildings,id'],
            'role_key' => ['nullable', 'string', 'max:30'],
            'status' => ['nullable', Rule::in(['active', 'disabled'])],
            'membership_status' => ['nullable', Rule::in(['active', 'invited', 'inactive', 'rejected'])],
        ]);
        $q = trim((string) ($filters['q'] ?? ''));
        $like = '%'.addcslashes($q, '%_\\').'%';

        $canViewFinance = auth('admin')->user()?->hasPermission('subscriptions.manage') ?? false;

        $usersQuery = User::query()
            ->with(['buildingRoles.building:id,name', 'buildingRoles.role:id,name_fa,key'])
            ->when($q !== '', fn ($query) => $query->where(function ($search) use ($like) {
                $search->where('mobile', 'like', $like)->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)->orWhere('company_name', 'like', $like)
                    ->orWhere('national_code', 'like', $like);
            }))
            ->when(! empty($filters['building_id']), fn ($query) => $query->whereHas('buildingRoles', fn ($m) => $m->where('building_id', $filters['building_id'])))
            ->when(! empty($filters['role_key']), fn ($query) => $query->whereHas('buildingRoles.role', fn ($role) => $role->where('key', $filters['role_key'])))
            ->when(! empty($filters['membership_status']), fn ($query) => $query->whereHas('buildingRoles', fn ($m) => $m->where('status', $filters['membership_status'])))
            ->when(! empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when($canViewFinance, function ($query) {
                $query->addSelect([
                    'latest_payment_status' => DB::table('subscription_payments')
                        ->select('status')->whereColumn('user_id', 'users.id')->orderByDesc('id')->limit(1),
                    'latest_payment_paid_at' => DB::table('subscription_payments')
                        ->select('paid_at')->whereColumn('user_id', 'users.id')->where('status', 'paid')->orderByDesc('id')->limit(1),
                    'paid_total' => DB::table('subscription_payments')
                        ->selectRaw('COALESCE(SUM(amount), 0)')->whereColumn('user_id', 'users.id')->where('status', 'paid'),
                    'latest_subscription_status' => DB::table('subscriptions')
                        ->select('status')->whereColumn('user_id', 'users.id')->orderByDesc('id')->limit(1),
                    'active_subscription_expires_at' => DB::table('subscriptions')
                        ->select('expires_at')->whereColumn('user_id', 'users.id')->where('status', 'active')->where('expires_at', '>', now())->orderByDesc('expires_at')->limit(1),
                ]);
            })
            ->orderByDesc('created_at');

        return view('admin.building-users.index', [
            'users' => $usersQuery->paginate(20)->withQueryString(),
            'filters' => array_merge(['q' => '', 'building_id' => '', 'role_key' => '', 'status' => '', 'membership_status' => ''], $filters),
            'buildings' => Building::query()->orderBy('name')->get(['id', 'name']),
            'roles' => Role::query()->where('is_active', true)->orderBy('name_fa')->get(['key', 'name_fa'])->unique('key')->values(),
            'canViewFinance' => $canViewFinance,
            'stats' => [
                'total' => User::query()->count(),
                'active' => User::query()->where('status', 'active')->count(),
                'verified' => User::query()->whereNotNull('mobile_verified_at')->count(),
                'memberships' => DB::table('building_user_roles')->where('status', 'active')->distinct()->count('user_id'),
            ],
        ]);
    }
}
