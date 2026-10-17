<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** نقش‌ها و سطوح دسترسی پنل مدیریت سیستم. */
class AdminRoleController extends Controller
{
    public function index()
    {
        $roles = AdminRole::query()->withCount('users')->orderBy('name')->get();
        $permCounts = \Illuminate\Support\Facades\DB::table('admin_role_permissions')
            ->selectRaw('admin_role_id, count(*) as c')->groupBy('admin_role_id')->pluck('c', 'admin_role_id');

        return view('admin.roles.index', ['roles' => $roles, 'permCounts' => $permCounts]);
    }

    public function create()
    {
        return view('admin.roles.form', $this->formData(null));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $role = AdminRole::create(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', 'نقش جدید ذخیره شد.');
    }

    public function edit(int $id)
    {
        $role = AdminRole::findOrFail($id);
        $this->authorizeRole($role);

        return view('admin.roles.form', $this->formData($role));
    }

    public function update(Request $request, int $id)
    {
        $role = AdminRole::findOrFail($id);
        $this->authorizeRole($role);
        $data = $this->validated($request, $role->id);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('admin.roles.index')->with('status', 'نقش به‌روز شد.');
    }

    public function destroy(int $id)
    {
        $role = AdminRole::withCount('users')->findOrFail($id);
        $this->authorizeRole($role);

        if ($role->users_count > 0) {
            return back()->with('error', 'این نقش به کاربر ادمین اختصاص دارد؛ ابتدا آن را از کاربران بردارید.');
        }

        $role->delete();

        return redirect()->route('admin.roles.index')->with('status', 'نقش حذف شد.');
    }

    private function actor(): AdminUser
    {
        /** @var AdminUser */
        return Auth::guard('admin')->user();
    }

    /** ادمین غیرسوپر نمی‌تواند نقشی را که مجوزهایی فراتر از مجوزهای خودش دارد ویرایش یا حذف کند. */
    private function authorizeRole(AdminRole $role): void
    {
        abort_unless($this->actor()->canGrant($role->permissionKeys()), 403, 'این نقش مجوزهایی دارد که شما به آن‌ها دسترسی ندارید.');
    }

    private function formData(?AdminRole $role): array
    {
        $actor = $this->actor();

        return [
            'role' => $role,
            'groups' => config('admin_permissions'),
            'selected' => $role?->permissionKeys() ?? [],
            'grantable' => $actor->is_super ? AdminUser::allPermissionKeys() : $actor->permissionKeys(),
        ];
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('admin_roles', 'name')->ignore($ignoreId)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in(AdminUser::allPermissionKeys())],
        ], [
            'name.unique' => 'نقشی با این نام قبلاً تعریف شده است.',
        ]);

        if (! $this->actor()->canGrant($data['permissions'] ?? [])) {
            throw ValidationException::withMessages([
                'permissions' => 'نمی‌توانید مجوزی را بدهید که خودتان ندارید.',
            ]);
        }

        return $data;
    }
}
