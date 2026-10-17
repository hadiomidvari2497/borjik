<?php

namespace App\Models;

use App\Enums\PersonType;
use App\Support\CurrentBuilding;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use App\Models\Concerns\Auditable;

class User extends Authenticatable
{
    use Notifiable, SoftDeletes, Auditable;

    protected $guarded = ['id'];

    protected $hidden = ['password', 'two_factor_secret', 'remember_token'];

    /** @var array<string, string[]> کش مجوزها فقط در طول همین درخواست */
    private array $permissionCache = [];

    protected function casts(): array
    {
        return [
            'person_type' => PersonType::class,
            'birth_date' => 'date',
            'mobile_verified_at' => 'datetime',
            'profile_completed_at' => 'datetime',
            'last_login_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'password' => 'hashed',
        ];
    }

    /** نام نمایشی: شخص حقیقی ← نام و نام خانوادگی، شخص حقوقی ← نام شرکت. */
    public function getDisplayNameAttribute(): string
    {
        return $this->person_type === PersonType::Legal
            ? (string) $this->company_name
            : trim($this->first_name.' '.$this->last_name);
    }

    public function buildingRoles()
    {
        return $this->hasMany(BuildingUserRole::class);
    }

    public function buildings()
    {
        return $this->belongsToMany(Building::class, 'building_user_roles')->wherePivot('status', 'active')->distinct();
    }

    /** کلید نقش‌های کاربر در یک ساختمان. */
    public function roleKeysIn(int $buildingId): array
    {
        return DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.user_id', $this->id)->where('bur.building_id', $buildingId)->where('bur.status', 'active')
            ->pluck('r.key')->all();
    }

    /** کلید مجوزهای کاربر در ساختمان (پیش‌فرض: ساختمان فعال). */
    public function permissionKeys(?int $buildingId = null): array
    {
        $buildingId ??= app(CurrentBuilding::class)->id();
        if (! $buildingId) {
            return [];
        }

        $currentBuilding = app(CurrentBuilding::class);
        $currentBuildingRoles = $currentBuilding->id() === $buildingId ? $currentBuilding->roles() : [];
        $cacheKey = $buildingId.':'.implode(',', $currentBuildingRoles);

        return $this->permissionCache[$cacheKey] ??= DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->join('role_permission as rp', 'rp.role_id', '=', 'bur.role_id')
            ->join('permissions as p', 'p.id', '=', 'rp.permission_id')
            ->where('bur.user_id', $this->id)->where('bur.building_id', $buildingId)->where('bur.status', 'active')
            ->when($currentBuildingRoles !== [], fn ($query) => $query->whereIn('r.key', $currentBuildingRoles))
            ->pluck('p.key')->unique()->values()->all();
    }

    public function hasPermission(string $key, ?int $buildingId = null): bool
    {
        return in_array($key, $this->permissionKeys($buildingId), true);
    }
}
