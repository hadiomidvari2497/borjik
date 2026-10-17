<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\DB;

class AdminUser extends Authenticatable
{
    protected $table = 'admin_users';

    protected $guarded = ['id'];

    protected $hidden = ['password', 'two_factor_secret', 'remember_token'];

    /** @var list<string>|null */
    private ?array $permissionCache = null;

    protected function casts(): array
    {
        return [
            'two_factor_enabled' => 'boolean',
            'is_super' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_user_role', 'admin_user_id', 'admin_role_id');
    }

    /** همهٔ کلیدهای مجوز تعریف‌شده در config/admin_permissions.php @return list<string> */
    public static function allPermissionKeys(): array
    {
        $keys = [];
        foreach (config('admin_permissions', []) as $group) {
            $keys = array_merge($keys, array_keys($group['items'] ?? []));
        }

        return $keys;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** @return list<string> */
    public function permissionKeys(): array
    {
        if ($this->is_super) {
            return self::allPermissionKeys();
        }

        return $this->permissionCache ??= DB::table('admin_role_permissions as p')
            ->join('admin_user_role as ur', 'ur.admin_role_id', '=', 'p.admin_role_id')
            ->where('ur.admin_user_id', $this->id)
            ->distinct()
            ->pluck('p.permission_key')
            ->all();
    }

    /** true اگر کاربر سوپرادمین باشد یا حداقل یکی از مجوزهای داده‌شده را داشته باشد. */
    public function hasPermission(string|array $keys): bool
    {
        if ($this->is_super) {
            return true;
        }

        return array_intersect((array) $keys, $this->permissionKeys()) !== [];
    }

    /** آیا این کاربر مجاز است همهٔ مجوزهای داده‌شده را به دیگری بدهد؟ (جلوگیری از ارتقای دسترسی) */
    public function canGrant(array $keys): bool
    {
        return $this->is_super || array_diff($keys, $this->permissionKeys()) === [];
    }
}
