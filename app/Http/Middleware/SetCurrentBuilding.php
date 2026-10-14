<?php

namespace App\Http\Middleware;

use App\Support\CurrentBuilding;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * ساختمان فعال را از session می‌خواند، عضویت فعال کاربر را در آن تأیید می‌کند و CurrentBuilding را پر می‌کند.
 * کاربر بدون ساختمان فقط به داشبورد (صفحه‌ی خوش‌آمدگویی) می‌رسد؛ سایر مسیرها او را به آنجا برمی‌گردانند.
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

        // کاربر بدون ساختمان (مثلاً بعد از اولین ورود) خطا نمی‌گیرد: فقط داشبورد خوش‌آمدگویی را می‌بیند و
        // بقیه‌ی بخش‌ها (که نیاز به ساختمان فعال دارند) او را به همان داشبورد برمی‌گردانند.
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
