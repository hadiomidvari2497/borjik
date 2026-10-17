<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Building;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $since = now()->subDays(30);
        $activeSubs = Subscription::where('status', 'active')->where('expires_at', '>', now());

        return view('admin.dashboard', [
            'usersCount' => User::count(),
            'newUsers' => User::where('created_at', '>=', $since)->count(),
            'buildingsCount' => Building::count(),
            'newBuildings' => Building::where('created_at', '>=', $since)->count(),
            'activeSubs' => (clone $activeSubs)->count(),
            'expiringSoon' => (clone $activeSubs)->where('expires_at', '<=', now()->addDays(7))->count(),
            'plansCount' => SubscriptionPlan::count(),
            'activePlans' => SubscriptionPlan::where('is_active', true)->count(),
        ]);
    }
}
