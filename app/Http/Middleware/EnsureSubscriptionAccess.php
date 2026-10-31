<?php

namespace App\Http\Middleware;

use App\Models\Building;
use App\Services\BuildingService;
use App\Support\CurrentBuilding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * دسترسی به امکانات پنل فقط در صورت داشتن اشتراک فعال مجاز است.
 * صفحه‌های مربوط به مشاهده/خرید اشتراک و داشبورد برای تمدید در دسترس می‌مانند.
 */
class EnsureSubscriptionAccess
{
    private const ALLOWED_ROUTES = [
        'dashboard',
        'subscription.*',
        'building.switch',
        'buildings.create',
        'buildings.store',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $buildingId = app(CurrentBuilding::class)->id();

        if ($buildingId) {
            $building = Building::find($buildingId);
            $subscription = $building
                ? app(BuildingService::class)->ensureActiveSubscription($building)
                : null;

            // Legacy buildings created before subscription enforcement may not have a subscription.
            // New buildings are still created only through the subscription-checked controller.
        } else {
            $subscription = app(BuildingService::class)->activeSubscription($user);
        }

        $legacyUnsubscribedBuilding = $buildingId
            && isset($building)
            && $building
            && ! $building->subscription_id
            && ! $user->subscriptions()->exists();

        if (! $legacyUnsubscribedBuilding && (! $subscription || ! $subscription->isActive())) {
            return redirect()->route('subscription.show')
                ->with('warning', 'مهلت اشتراک شما به پایان رسیده است. برای ادامه استفاده از امکانات پنل، اشتراک خود را تمدید کنید.');
        }

        return $next($request);
    }
}
