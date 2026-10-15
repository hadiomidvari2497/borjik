<?php

namespace App\Http\Middleware;

use App\Support\CurrentBuilding;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class SetCurrentBuilding
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user, 401);

        $memberships = DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->join('buildings as b', 'b.id', '=', 'bur.building_id')
            ->where('bur.user_id', $user->id)
            ->where('bur.status', 'active')->whereNull('b.deleted_at')
            ->orderBy('bur.id')
            ->get(['bur.id as membership_id', 'bur.building_id', 'r.key as role_key']);

        if ($memberships->isEmpty()) {
            $request->session()->forget(['current_building_id', 'current_building_user_role_id']);
            app(CurrentBuilding::class)->clear();
            if ($request->routeIs('dashboard')) {
                return $next($request);
            }
            return redirect()->route('dashboard')->with('warning', 'برای استفاده از این بخش ابتدا ساختمان بسازید یا دعوت‌نامه‌ی یک ساختمان را بپذیرید.');
        }

        $membershipId = (int) $request->session()->get('current_building_user_role_id', 0);
        $membership = $memberships->firstWhere('membership_id', $membershipId);
        if (! $membership) {
            $buildingId = (int) $request->session()->get('current_building_id', 0);
            $membership = $buildingId ? $memberships->firstWhere('building_id', $buildingId) : null;
            $membership ??= $memberships->first();
        }

        $request->session()->put('current_building_id', (int) $membership->building_id);
        $request->session()->put('current_building_user_role_id', (int) $membership->membership_id);
        app(CurrentBuilding::class)->set((int) $membership->building_id, [$membership->role_key]);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        app(CurrentBuilding::class)->clear();
    }
}
