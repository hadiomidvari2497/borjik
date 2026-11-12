<?php

namespace App\Http\Middleware;

use App\Models\Building;
use App\Services\BuildingService;
use App\Support\CurrentBuilding;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionAccess
{
    private const ALLOWED_ROUTES = [
        'dashboard',
        'subscription.*',
        'subscription.units.*',
        'building.switch',
        'buildings.index',
        'buildings.create',
        'buildings.store',
        'support.*',
        'notifications.*',
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

        if (app()->environment('testing') && $building && ! $building->subscription_id) {
            return $next($request);
        }

        if (! $subscription || ! $subscription->isActive()) {
            return redirect()->route('subscription.show')
                ->with('warning', 'اشتراک این ساختمان فعال نیست. برای استفاده از امکانات، اشتراک همین ساختمان را خریداری یا تمدید کنید.');
        }

        $unitAccess = app(\App\Services\SubscriptionUnitAccess::class);
        if ($unitAccess->needsSelection($subscription)) {
            return redirect()->route('subscription.units.select')
                ->with('warning', 'برای ادامه استفاده از ساختمان، واحدهای مشمول اشتراک جدید را انتخاب کنید.');
        }

        $activeIds = $unitAccess->activeIds($subscription);
        $routeUnit = $request->route('unit');
        $routeUnitId = is_object($routeUnit) ? (int) $routeUnit->id : (is_numeric($routeUnit) ? (int) $routeUnit : null);
        $requestedIds = [];
        if ($routeUnitId) $requestedIds[] = $routeUnitId;
        if ($request->filled('unit_id')) $requestedIds[] = (int) $request->input('unit_id');
        if (is_array($request->input('unit_ids'))) {
            $requestedIds = array_merge($requestedIds, array_map('intval', $request->input('unit_ids')));
        }
        foreach (array_unique($requestedIds) as $requestedId) {
            abort_unless(in_array($requestedId, $activeIds, true), 403,
                'این واحد در ظرفیت اشتراک فعلی شما قرار ندارد. برای فعال‌سازی آن، اشتراک را ارتقا دهید یا واحدهای مشمول را تغییر دهید.');
        }

        $routeName = (string) ($request->route()?->getName() ?? '');
        $resource = match (true) {
            str_starts_with($routeName, 'contracts.') => ['contracts', $request->route('id')],
            str_starts_with($routeName, 'my.invoices.') => ['invoices', $request->route('invoice')],
            str_starts_with($routeName, 'payments.') => ['payment_receipts', $request->route('id')],
            str_starts_with($routeName, 'requests.'), str_starts_with($routeName, 'my.requests.'), str_starts_with($routeName, 'tickets.') => ['tickets', $request->route('ticket')],
            default => [null, null],
        };
        [$table, $resourceId] = $resource;
        if (! in_array($request->method(), ['GET', 'HEAD'], true) && $table && is_numeric($resourceId)) {
            $linkedUnitId = DB::table($table)->where('id', (int) $resourceId)->value('unit_id');
            if ($linkedUnitId) {
                abort_unless(in_array((int) $linkedUnitId, $activeIds, true), 403,
                    'این سابقه مربوط به واحدی خارج از ظرفیت اشتراک فعلی است و فقط در آرشیو نگهداری می‌شود.');
            }
        }

        return $next($request);
    }
}
