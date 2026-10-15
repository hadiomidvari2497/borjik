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
        return view('admin.dashboard', [
            'usersCount' => User::count(),
            'buildingsCount' => Building::count(),
            'activeSubs' => Subscription::where('status', 'active')->where('expires_at', '>', now())->count(),
            'plansCount' => SubscriptionPlan::count(),
            'activePlans' => SubscriptionPlan::where('is_active', true)->count(),
        ]);
    }
}
