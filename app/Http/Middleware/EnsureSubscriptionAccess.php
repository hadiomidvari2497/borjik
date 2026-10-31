<?php

namespace App\\Http\\Middleware;

use App\\Models\\Building;
use App\\Services\\BuildingService;
use App\\Support\\CurrentBuilding;
use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

class EnsureSubscriptionAccess
{
    private const ALLOWED_ROUTES = [
        'dashboard',
        'subscription.*',
        'building.switch',
        'buildings.index',
        'buildings.create',
        'buildings.store',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if (! $request->user()) {
            return $next($request);
        }

        $buildingId = app(CurrentBuilding::class)->id();
        $building = $buildingId ? Building::find($buildingId) : null;
        $subscription = $building
            ? app(BuildingService::class)->ensureActiveSubscription($building)
            : null;

        if (! $subscription || ! $subscription->isActive()) {
            return redirect()->route('subscription.show')
                ->with('warning', 'اشتراک این ساختمان فعال نیست. برای استفاده از امکانات، اشتراک همین ساختمان را خریداری یا تمدید کنید.');
        }

        return $next($request);
    }
}
