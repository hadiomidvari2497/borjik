<?php

namespace App\View\Composers;

use App\Models\Building;
use App\Models\Role;
use App\Services\Ui\MenuBuilder;
use App\Support\CurrentBuilding;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** داده‌ی مشترک لایوت اصلی: منو، ساختمان‌های کاربر (برای سوئیچ)، ساختمان و نقش‌های فعلی. */
class LayoutComposer
{
    public function __construct(private readonly MenuBuilder $menu)
    {
    }

    public function compose(View $view): void
    {
        $user = auth()->user();
        $current = app(CurrentBuilding::class);

        $memberships = DB::table('building_user_roles as bur')
            ->join('buildings as b', 'b.id', '=', 'bur.building_id')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.user_id', $user->id)->where('bur.status', 'active')->whereNull('b.deleted_at')
            ->orderBy('b.name')
            ->get(['b.id', 'b.name', 'r.name_fa']);

        $buildings = $memberships->groupBy('id')->map(fn ($rows) => [
            'id' => $rows[0]->id,
            'name' => $rows[0]->name,
            'roles' => $rows->pluck('name_fa')->implode('، '),
        ])->values();

        $view->with([
            'menu' => $current->id() ? $this->menu->build($user) : $this->menu->preview(),
            'myBuildings' => $buildings,
            'currentBuilding' => $current->id() ? Building::find($current->id()) : null,
            'currentRoles' => Role::whereIn('key', $current->roles())->pluck('name_fa')->implode('، '),
        ]);
    }
}
