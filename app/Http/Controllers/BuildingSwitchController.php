<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BuildingSwitchController extends Controller
{
    /** رفتن به یک مقصد مجاز (ساختمان + نقش). */
    public function __invoke(Request $request, int $building)
    {
        $query = DB::table('building_user_roles as bur')
            ->join('buildings as b', 'b.id', '=', 'bur.building_id')
            ->where('bur.user_id', $request->user()->id)
            ->where('bur.building_id', $building)
            ->where('bur.status', 'active')->whereNull('b.deleted_at');

        if ($request->filled('membership_id')) {
            $query->where('bur.id', (int) $request->input('membership_id'));
        }

        $membership = $query->orderBy('bur.id')->first(['bur.id', 'bur.building_id']);
        abort_unless($membership, 403);

        $request->session()->put('current_building_id', (int) $membership->building_id);
        $request->session()->put('current_building_user_role_id', (int) $membership->id);

        return redirect()->route('dashboard');
    }
}
