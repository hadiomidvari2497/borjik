<?php

namespace App\Http\Middleware;

use App\Support\CurrentBuilding;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * ساختمان فعال را از session می‌خواند، عضویت فعال کاربر را در آن تأیید می‌کند و CurrentBuilding را پر می‌کند.
 * کاربر بدون ساختمان فقط به داشبورد (خوش‌آمدگویی) می‌رسد؛ سایر مسیرهای نیازمند ساختمان به داشبورد برمی‌گردند.
 */
class SetCurrentBuilding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $rows = DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.user_id', $user->id)
            ->where('bur.status', 'active')
            ->get(['bur.building_id', 'r.key']);

        $byBuilding = $rows->groupBy('building_id');

        // کاربر بدون ساختمان: داشبورد خوش‌آمدگویی را می‌بیند.
        // مسیرهای اشتراک و ساخت ساختمان خارج از این middleware هستند.
        if ($byBuilding->isEmpty()) {
            if ($request->routeIs('dashboard')) {
                return $next($request);
            }

            return redirect()->route('dashboard')->with('warning', 'برای استفاده از این بخش ابتدا ساختمان بسازید یا دعوت‌نامه‌ی یک ساختمان را بپذیرید.');
        }

        $buildingId = (int) $request->session()->get('current_building_id', 0);

        if (! $byBuilding->has($buildingId)) {
            $buildingId = (int) $byBuilding->keys()->first();
            $request->session()->put('current_building_id', $buildingId);
        }

        app(CurrentBuilding::class)->set($buildingId, $byBuilding[$buildingId]->pluck('key')->all());

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(CurrentBuilding::class)->clear();
    }
}
