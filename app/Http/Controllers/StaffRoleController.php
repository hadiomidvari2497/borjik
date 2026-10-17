<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class StaffRoleController extends Controller
{
    /** Only operational permissions can be granted to a staff role. */
    private const PERMISSIONS = [
        'ticket.view.assigned', 'ticket.respond', 'request.view.domain',
        'event.view', 'document.view', 'phonebook.personal', 'report.view.own',
        'notification.view', 'building.view', 'shift.view', 'supply.manage',
    ];

    public function index()
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $roles = Role::where('building_id', $buildingId)->where('is_staff', true)->orderBy('name_fa')->with('permissions')->get();
        $permissions = Permission::whereIn('key', self::PERMISSIONS)->orderBy('group_name')->orderBy('key')->get();

        return view('staff-roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $data = $request->validate([
            'name_fa' => ['required', 'string', 'min:2', 'max:60'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(self::PERMISSIONS)],
        ]);
        $key = 'custom_'.Str::lower(Str::random(16));

        $role = DB::transaction(function () use ($data, $buildingId, $key) {
            $role = Role::create([
                'building_id' => $buildingId,
                'key' => $key,
                'name_fa' => trim($data['name_fa']),
                'is_staff' => true,
                'is_default' => false,
                'is_active' => true,
            ]);
            $role->permissions()->sync(Permission::whereIn('key', $data['permissions'] ?? [])->pluck('id')->all());
            return $role;
        });

        return redirect()->route('staff-roles.index')->with('status', 'نقش جدید ایجاد شد.');
    }

    public function update(Request $request, int $role)
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $item = Role::where('building_id', $buildingId)->where('is_staff', true)->findOrFail($role);
        $data = $request->validate([
            'name_fa' => ['required', 'string', 'min:2', 'max:60'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(self::PERMISSIONS)],
        ]);

        DB::transaction(function () use ($item, $data) {
            $item->update(['name_fa' => trim($data['name_fa'])]);
            $item->permissions()->sync(Permission::whereIn('key', $data['permissions'] ?? [])->pluck('id')->all());
        });

        return redirect()->route('staff-roles.index')->with('status', 'نقش به‌روزرسانی شد.');
    }

    public function destroy(int $role)
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $item = Role::where('building_id', $buildingId)->where('is_staff', true)->findOrFail($role);

        if (DB::table('building_user_roles')->where('building_id', $buildingId)->where('role_id', $item->id)->exists()
            || DB::table('tickets')->where('building_id', $buildingId)->where('target_role_id', $item->id)->exists()
            || DB::table('invitations')->where('building_id', $buildingId)->where('role_id', $item->id)->exists()) {
            return back()->withErrors(['general' => 'این نقش هنوز به پرسنل، دعوت‌نامه یا تیکت متصل است؛ ابتدا تخصیص‌های آن را منتقل کنید.']);
        }

        $item->delete();

        return redirect()->route('staff-roles.index')->with('status', 'نقش حذف شد.');
    }
}
