<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminRole;
use App\Models\AdminUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** مدیریت کاربران پنل مدیریت سیستم (جدا از کاربران ساختمان‌ها). */
class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $like = '%'.addcslashes($q, '%_\\').'%';

        $users = AdminUser::query()
            ->with('roles:id,name')
            ->when($q !== '', fn ($query) => $query->where(
                fn ($w) => $w->where('username', 'like', $like)->orWhere('name', 'like', $like)
            ))
            ->orderByDesc('is_super')
            ->orderBy('username')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'q' => $q, 'me' => $this->actor()]);
    }

    public function create()
    {
        return view('admin.users.form', [
            'user' => null,
            'roles' => $this->assignableRoles(),
            'me' => $this->actor(),
        ]);
    }

    public function store(Request $request)
    {
        $actor = $this->actor();

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9_.-]+$/', 'unique:admin_users,username'],
            'name' => ['nullable', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'max:100', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'is_super' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:admin_roles,id'],
        ], [
            'username.regex' => 'نام کاربری فقط می‌تواند شامل حروف انگلیسی، عدد و علامت‌های . _ - باشد.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'password.confirmed' => 'تکرار رمز عبور با رمز عبور یکسان نیست.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
        ]);

        $roleIds = $this->checkedRoleIds($data['roles'] ?? [], $actor);

        $user = AdminUser::create([
            'username' => $data['username'],
            'name' => $data['name'] ?? null ?: null,
            'password' => $data['password'],
            'status' => $data['status'],
            'is_super' => $actor->is_super && $request->boolean('is_super'),
        ]);
        $user->roles()->sync($roleIds);

        return redirect()->route('admin.users.index')->with('status', 'کاربر ادمین ایجاد شد.');
    }

    public function edit(int $id)
    {
        $user = AdminUser::with('roles:id')->findOrFail($id);
        $this->authorizeTarget($user);

        return view('admin.users.form', [
            'user' => $user,
            'roles' => $this->assignableRoles($user),
            'me' => $this->actor(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $actor = $this->actor();
        $user = AdminUser::findOrFail($id);
        $this->authorizeTarget($user);
        $isSelf = $user->id === $actor->id;

        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:60', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('admin_users', 'username')->ignore($user->id)],
            'name' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'max:100', 'confirmed'],
            'status' => ['required', Rule::in(['active', 'disabled'])],
            'is_super' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['integer', 'exists:admin_roles,id'],
        ], [
            'username.regex' => 'نام کاربری فقط می‌تواند شامل حروف انگلیسی، عدد و علامت‌های . _ - باشد.',
            'username.unique' => 'این نام کاربری قبلاً ثبت شده است.',
            'password.confirmed' => 'تکرار رمز عبور با رمز عبور یکسان نیست.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
        ]);

        // کاربر نمی‌تواند وضعیت، سوپرادمین‌بودن و نقش‌های خودش را تغییر دهد (جلوگیری از قفل‌شدن/ارتقای خود)
        $status = $isSelf ? $user->status : $data['status'];
        $isSuper = ($isSelf || ! $actor->is_super) ? $user->is_super : $request->boolean('is_super');

        if ($user->is_super && $user->isActive() && (! $isSuper || $status !== 'active') && $this->isLastActiveSuper($user)) {
            throw ValidationException::withMessages([
                'status' => 'حداقل یک سوپرادمین فعال باید در سامانه باقی بماند.',
            ]);
        }

        $update = [
            'username' => $data['username'],
            'name' => $data['name'] ?? null ?: null,
            'status' => $status,
            'is_super' => $isSuper,
        ];
        if (! empty($data['password'])) {
            $update['password'] = $data['password'];
        }
        $user->update($update);

        if (! $isSelf) {
            $user->roles()->sync($this->checkedRoleIds($data['roles'] ?? [], $actor, $user));
        }

        return redirect()->route('admin.users.index')->with('status', 'اطلاعات کاربر ادمین به‌روز شد.');
    }

    public function destroy(int $id)
    {
        $actor = $this->actor();
        $user = AdminUser::findOrFail($id);
        $this->authorizeTarget($user);

        if ($user->id === $actor->id) {
            return back()->with('error', 'حذف حساب کاربری خودتان ممکن نیست.');
        }

        if ($user->is_super && $user->isActive() && $this->isLastActiveSuper($user)) {
            return back()->with('error', 'آخرین سوپرادمین فعال را نمی‌توان حذف کرد.');
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()->route('admin.users.index')->with('status', 'کاربر ادمین حذف شد.');
    }

    private function actor(): AdminUser
    {
        /** @var AdminUser */
        return Auth::guard('admin')->user();
    }

    /** ادمین غیرسوپر اجازهٔ ویرایش/حذف سوپرادمین‌ها را ندارد. */
    private function authorizeTarget(AdminUser $target): void
    {
        abort_if($target->is_super && ! $this->actor()->is_super, 403, 'فقط سوپرادمین می‌تواند سوپرادمین‌ها را مدیریت کند.');
    }

    private function isLastActiveSuper(AdminUser $user): bool
    {
        return ! AdminUser::query()
            ->where('is_super', true)
            ->where('status', 'active')
            ->where('id', '!=', $user->id)
            ->exists();
    }

    /**
     * نقش‌هایی که این ادمین اجازهٔ دادنشان را دارد (مجوزهای نقش ⊆ مجوزهای خودش).
     * نقش‌هایی که کاربر هدف از قبل دارد همیشه در فهرست می‌آیند تا با ذخیره حذف نشوند.
     */
    private function assignableRoles(?AdminUser $target = null)
    {
        $actor = $this->actor();
        $keep = $target ? $target->roles()->pluck('admin_roles.id')->all() : [];

        return AdminRole::query()->orderBy('name')->get()
            ->filter(fn (AdminRole $r) => in_array($r->id, $keep, true) || $actor->canGrant($r->permissionKeys()))
            ->values();
    }

    /** @return list<int> */
    private function checkedRoleIds(array $ids, AdminUser $actor, ?AdminUser $target = null): array
    {
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $allowed = $this->assignableRoles($target)->pluck('id')->all();

        if (array_diff($ids, $allowed) !== []) {
            throw ValidationException::withMessages([
                'roles' => 'شما فقط می‌توانید نقش‌هایی را بدهید که مجوزهایشان از مجوزهای خودتان فراتر نرود.',
            ]);
        }

        return $ids;
    }
}
