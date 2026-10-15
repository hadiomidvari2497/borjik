<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LoginDestinationService
{
    public function applyAfterLogin(Request $request, User $user): void
    {
        $memberships = DB::table('building_user_roles as bur')
            ->join('buildings as b', 'b.id', '=', 'bur.building_id')
            ->where('bur.user_id', $user->id)->where('bur.status', 'active')->whereNull('b.deleted_at')
            ->orderBy('bur.id')->get(['bur.id as membership_id', 'bur.building_id']);

        $target = null;
        if ($user->login_destination_mode === 'fixed' && $user->preferred_building_user_role_id) {
            $target = $memberships->firstWhere('membership_id', (int) $user->preferred_building_user_role_id);
        }
        if (! $target && $user->last_building_user_role_id) {
            $target = $memberships->firstWhere('membership_id', (int) $user->last_building_user_role_id);
        }
        $target ??= $memberships->first();

        if (! $target) {
            $request->session()->forget(['current_building_id', 'current_building_user_role_id']);
            return;
        }

        $request->session()->put('current_building_id', (int) $target->building_id);
        $request->session()->put('current_building_user_role_id', (int) $target->membership_id);
    }

    public function rememberOnLogout(Request $request, User $user): void
    {
        $membershipId = (int) $request->session()->get('current_building_user_role_id', 0);
        if ($membershipId < 1) return;

        $valid = DB::table('building_user_roles as bur')
            ->join('buildings as b', 'b.id', '=', 'bur.building_id')
            ->where('bur.id', $membershipId)->where('bur.user_id', $user->id)
            ->where('bur.status', 'active')->whereNull('b.deleted_at')->exists();

        if ($valid) {
            $user->forceFill(['last_building_user_role_id' => $membershipId])->save();
        }
    }
}
