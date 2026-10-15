<?php

namespace App\Http\Middleware;

use App\Models\Building;
use App\Support\CurrentBuilding;
use App\Services\BuildingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** با انقضای اشتراک، افزودن ساختمان/بلوک/واحد غیرفعال می‌شود و داده‌ها فقط‌خواندنی می‌مانند (بند ۴.۴). */
class RequireActiveSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $building = Building::findOrFail(app(CurrentBuilding::class)->id());
        $subscription = app(BuildingService::class)->ensureActiveSubscription($building);

        abort_unless(
            $subscription && $subscription->isActive(),
            403,
            'اشتراک این ساختمان منقضی شده است؛ برای افزودن اطلاعات جدید اشتراک را تمدید کنید.'
        );

        return $next($request);
    }
}
