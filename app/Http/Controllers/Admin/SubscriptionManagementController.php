<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Support\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionManagementController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $like = '%'.addcslashes(MobileNumber::toLatinDigits($q), '%_\\').'%';

        $buildings = Building::query()
            ->with(['createdBy', 'subscription.plan'])
            ->when($q !== '', fn ($query) => $query->where(function ($where) use ($like) {
                $where->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhereHas('createdBy', fn ($owner) => $owner
                        ->where('mobile', 'like', $like)
                        ->orWhere('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('company_name', 'like', $like));
            }))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.subscriptions.index', compact('buildings', 'q'));
    }

    public function create(Request $request)
    {
        $building = Building::with('createdBy')->findOrFail((int) $request->query('building_id'));
        $plans = SubscriptionPlan::query()
            ->orderByDesc('is_active')
            ->orderBy('price')
            ->orderBy('units_limit')
            ->get();
        $currentSubscription = Subscription::query()
            ->with('plan')
            ->where('building_id', $building->id)
            ->orderByDesc('id')
            ->first();

        return view('admin.subscriptions.form', compact('building', 'plans', 'currentSubscription'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'building_id' => ['required', 'integer', 'exists:buildings,id'],
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $plan = SubscriptionPlan::findOrFail($data['plan_id']);

        $subscription = DB::transaction(function () use ($data, $plan) {
            $building = Building::query()->whereKey($data['building_id'])->lockForUpdate()->firstOrFail();

            Subscription::query()
                ->where('building_id', $building->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            $newSubscription = Subscription::create([
                'user_id' => $building->created_by,
                'building_id' => $building->id,
                'plan_id' => $plan->id,
                'units_limit' => $plan->units_limit,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(max(1, (int) $plan->duration_months)),
                'status' => 'active',
            ]);

            $building->update(['subscription_id' => $newSubscription->id]);

            return $newSubscription;
        });

        return redirect()
            ->route('admin.subscriptions.index', array_filter(['q' => $data['q'] ?? null], fn ($value) => $value !== null && $value !== ''))
            ->with('status', 'اشتراک «'.$plan->name.'» برای ساختمان فعال شد و تا '.$subscription->expires_at->format('Y-m-d').' اعتبار دارد.');
    }
}
