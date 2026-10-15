<?php

namespace App\View\Composers;

use App\Models\Building;
use App\Models\Role;
use App\Services\Ui\MenuBuilder;
use App\Support\CurrentBuilding;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * داده‌ی مشترک لایوت اصلی.
 * اگر middleware «building» روی مسیر نباشد (مثلاً اشتراک یا پروفایل)،
 * ساختمان فعال از session پر می‌شود تا منو و سوئیچر قفل/خام نشود.
 */
class LayoutComposer
{
    public function __construct(private readonly MenuBuilder $menu)
    {
    }

    public function compose(View $view): void
    {
        $user = auth()->user();
        $current = app(CurrentBuilding::class);

        if ($user && $current->id() === null) {
            $this->hydrateCurrentBuilding($user, $current);
        }

        $destinations = DB::table('building_user_roles as bur')
            ->join('buildings as b', 'b.id', '=', 'bur.building_id')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.user_id', $user->id)
            ->where('bur.status', 'active')
            ->whereNull('b.deleted_at')
            ->orderBy('b.name')
            ->orderBy('r.name_fa')
            ->get(['bur.id as membership_id', 'bur.building_id as id', 'b.name', 'r.name_fa'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'membership_id' => (int) $row->membership_id,
                'name' => $row->name,
                'roles' => $row->name_fa,
            ]);

        $view->with([
            'menu' => $current->id() ? $this->menu->build($user) : $this->menu->preview(),
            'myBuildings' => $destinations,
            'currentBuilding' => $current->id() ? Building::find($current->id()) : null,
            'currentRoles' => Role::whereIn('key', $current->roles())->pluck('name_fa')->implode('، '),
        ]);
    }

    private function hydrateCurrentBuilding($user, CurrentBuilding $current): void
    {
        $memberships = DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->join('buildings as b', 'b.id', '=', 'bur.building_id')
            ->where('bur.user_id', $user->id)
            ->where('bur.status', 'active')
            ->whereNull('b.deleted_at')
            ->orderBy('bur.id')
            ->get(['bur.id as membership_id', 'bur.building_id', 'r.key as role_key']);

        if ($memberships->isEmpty()) {
            return;
        }

        $membershipId = (int) session('current_building_user_role_id', 0);
        $membership = $memberships->firstWhere('membership_id', $membershipId);

        if (! $membership) {
            $buildingId = (int) session('current_building_id', 0);
            $membership = $buildingId ? $memberships->firstWhere('building_id', $buildingId) : null;
            $membership ??= $memberships->first();
        }

        session([
            'current_building_id' => (int) $membership->building_id,
            'current_building_user_role_id' => (int) $membership->membership_id,
        ]);

        $current->set((int) $membership->building_id, [$membership->role_key]);
    }
}
