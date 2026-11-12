<?php

namespace App\\Http\\Controllers;

use App\\Models\\Building;
use App\\Services\\BuildingService;
use App\\Services\\SubscriptionUnitAccess;
use App\\Support\\CurrentBuilding;
use Illuminate\\Http\\Request;

class SubscriptionUnitController extends Controller
{
    public function show(SubscriptionUnitAccess $access, BuildingService $buildings)
    {
        $building = Building::findOrFail(app(CurrentBuilding::class)->id());
        $subscription = $buildings->ensureActiveSubscription($building);
        abort_unless($subscription && $subscription->isActive(), 403);

        return view('subscription.units-select', [
            'building' => $building,
            'subscription' => $subscription,
            'units' => $access->unitsForSelection($building),
            'selectedIds' => $access->activeIds($subscription),
            'requiredCount' => min($access->totalUnits($building), (int) $subscription->units_limit),
        ]);
    }

    public function store(Request $request, SubscriptionUnitAccess $access, BuildingService $buildings)
    {
        $building = Building::findOrFail(app(CurrentBuilding::class)->id());
        $subscription = $buildings->ensureActiveSubscription($building);
        abort_unless($subscription && $subscription->isActive(), 403);

        $validated = $request->validate([
            'unit_ids' => ['required', 'array'],
            'unit_ids.*' => ['required', 'integer', 'distinct'],
        ]);

        $access->saveSelection($subscription, $validated['unit_ids']);

        return redirect()->route('dashboard')
            ->with('status', 'واحدهای مشمول اشتراک با موفقیت ذخیره شدند.');
    }
}
