<?php

namespace App\Http\Controllers;

use App\Services\BuildingService;
use App\Services\Dashboard\DashboardData;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /** داشبورد نقش‌محور: مدیریت ← کامل، پرسنل ← تسک‌های من، ساکنین ← صورت‌حساب و درخواست‌های من. */
    public function __invoke(Request $request, DashboardData $data, BuildingService $buildings)
    {
        $current = app(CurrentBuilding::class);

        if ($current->id() === null) {
            return view('dashboard.welcome', [
                'subscription' => $buildings->activeSubscription($request->user()),
                'pendingInvites' => DB::table('building_user_roles')->where('user_id', $request->user()->id)->where('status', 'invited')->count(),
            ]);
        }

        if ($current->hasRole('owner', 'manager')) {
            return view('dashboard.management', $data->management());
        }
        if ($current->hasRole('security', 'cleaning', 'maintenance')) {
            return view('dashboard.staff', $data->staff($request->user()));
        }

        return view('dashboard.resident', $data->resident($request->user()));
    }
}
