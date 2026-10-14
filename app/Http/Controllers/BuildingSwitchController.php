<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuildingSwitchController extends Controller
{
    /** سوئیچ سریع بین ساختمان‌ها = تغییر Tenant فعلی. */
    public function __invoke(Request $request, int $building)
    {
        $isMember = DB::table('building_user_roles')
            ->where('user_id', $request->user()->id)
            ->where('building_id', $building)
            ->where('status', 'active')
            ->exists();

        abort_unless($isMember, 403);

        $request->session()->put('current_building_id', $building);

        return redirect()->route('dashboard');
    }
}
