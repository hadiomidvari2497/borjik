<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class AdminRole extends Model
{
    protected $table = 'admin_roles';
    protected $guarded = ['id'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_user_role', 'admin_role_id', 'admin_user_id');
    }

    /** @return list<string> */
    public function permissionKeys(): array
    {
        return DB::table('admin_role_permissions')
            ->where('admin_role_id', $this->id)
            ->orderBy('permission_key')
            ->pluck('permission_key')
            ->all();
    }

    /** مجوزهای نقش را با فهرست داده‌شده جایگزین می‌کند (فقط کلیدهای معتبر کاتالوگ). */
    public function syncPermissions(array $keys): void
    {
        $keys = array_values(array_unique(array_intersect($keys, AdminUser::allPermissionKeys())));

        DB::transaction(function () use ($keys) {
            DB::table('admin_role_permissions')->where('admin_role_id', $this->id)->delete();

            if ($keys !== []) {
                DB::table('admin_role_permissions')->insert(
                    array_map(fn (string $key) => ['admin_role_id' => $this->id, 'permission_key' => $key], $keys)
                );
            }
        });
    }
}
