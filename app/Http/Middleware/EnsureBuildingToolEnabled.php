<?php

namespace App\Http\Middleware;

use App\Support\BuildingTools;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBuildingToolEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! BuildingTools::routeEnabled($request->route()?->getName())) {
            return redirect()->route('dashboard')
                ->with('warning', 'این ابزار فعلاً توسط مدیریت سامانه غیرفعال شده است.');
        }

        return $next($request);
    }
}
