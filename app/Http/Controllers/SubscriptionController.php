<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\BuildingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** صفحهٔ اشتراک برای کاربر (بدون نیاز به ساختمان فعال). */
class SubscriptionController extends Controller
{
    public function show(Request $request, BuildingService $buildings)
    {
        $user = $request->user();
        $current = $buildings->activeSubscription($user);
        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('units_limit')
            ->orderBy('duration_months')
            ->get();

        return view('subscription.show', [
            'subscription' => $current,
            'plans' => $plans,
        ]);
    }

    /**
     * انتخاب پلن.
     * تا زمان اتصال درگاه پرداخت، اشتراک بلافاصله فعال می‌شود تا کاربر بتواند ساختمان بسازد.
     */
    public function subscribe(Request $request, int $id)
    {
        $plan = SubscriptionPlan::query()->where('is_active', true)->findOrFail($id);
        $user = $request->user();

        DB::transaction(function () use ($user, $plan) {
            Subscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->update(['status' => 'expired']);

            Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'units_limit' => $plan->units_limit,
                'starts_at' => now(),
                'expires_at' => now()->addMonths(max(1, (int) $plan->duration_months)),
                'status' => 'active',
            ]);
        });

        return redirect()
            ->route('buildings.create')
            ->with('status', 'اشتراک «'.$plan->name.'» فعال شد. حالا می‌توانید ساختمان بسازید.');
    }
}
