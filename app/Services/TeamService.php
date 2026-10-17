<?php

namespace App\Services;

use App\Exceptions\TeamException;
use App\Models\BuildingUserRole;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\InvitationService;
use App\Support\CurrentBuilding;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * مدیریت پرسنل و اعضای ساختمان فعال (بند ۴.۸ و ۴.۹ سند).
 * - هیچ‌کس حذف نمی‌شود؛ فعال‌ها «غیرفعال» می‌شوند و فقط دعوتِ هنوزپذیرفته‌نشده لغو (حذف) می‌شود.
 * - فقط مالک ساختمان می‌تواند نقش «مالک» و «مدیر» را تعیین، تغییر یا غیرفعال کند.
 * - کسی نمی‌تواند خودش را غیرفعال کند یا نقش‌هایش را تغییر دهد، و آخرین مالک فعال هرگز از دست نمی‌رود.
 */
class TeamService
{
    public const STAFF = ['owner', 'manager', 'security', 'cleaning', 'maintenance'];
    public const MEMBERS = ['unit_owner', 'tenant'];
    private const PROTECTED = ['owner', 'manager'];

    public static function rolesOf(string $scope, ?int $buildingId = null): array
    {
        if ($scope !== 'staff') return self::MEMBERS;
        if (! $buildingId) return self::STAFF;
        $buildingKeys = Role::where('building_id', $buildingId)->where('is_staff', true)->where('is_active', true)->pluck('key')->all();
        $initialized = (bool) DB::table('buildings')->where('id', $buildingId)->value('staff_roles_initialized');
        return array_values(array_unique(array_merge(['owner', 'manager'], $buildingKeys ?: ($initialized ? [] : ['security', 'cleaning', 'maintenance']))));
    }

    /** فهرست افراد یک گروه (پرسنل یا اعضا) با نقش‌ها و وضعیت کلی. @return Collection<int, array<string,mixed>> */
    public function list(int $buildingId, string $scope): Collection
    {
        $rows = DB::table('building_user_roles as bur')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->join('users as u', 'u.id', '=', 'bur.user_id')
            ->where('bur.building_id', $buildingId)->whereIn('r.key', self::rolesOf($scope, $buildingId))->whereNull('u.deleted_at')
            ->orderBy('u.last_name')->orderBy('u.first_name')
            ->get(['bur.user_id', 'bur.status', 'bur.joined_at', 'bur.invited_at', 'r.key', 'r.name_fa', 'u.first_name', 'u.last_name', 'u.company_name', 'u.person_type', 'u.mobile']);

        return $rows->groupBy('user_id')->map(function ($g) {
            $first = $g[0];
            $statuses = $g->pluck('status')->all();

            return [
                'user_id' => (int) $first->user_id,
                'name' => $first->person_type === 'legal' ? (string) $first->company_name : trim($first->first_name.' '.$first->last_name),
                'mobile' => $first->mobile,
                'roles' => $g->pluck('name_fa', 'key')->all(),
                'status' => in_array('active', $statuses, true) ? 'active' : (in_array('invited', $statuses, true) ? 'invited' : (in_array('rejected', $statuses, true) ? 'rejected' : 'inactive')),
                'joined_at' => $g->pluck('joined_at')->filter()->min(),
            ];
        })->values();
    }

    /** واحدهای فعلی هر مالک/مستأجر. @return array<int, string[]> */
    public function unitLabels(int $buildingId, array $userIds): array
    {
        if (! $userIds) {
            return [];
        }

        return DB::table('unit_parties as p')
            ->join('units as u', 'u.id', '=', 'p.unit_id')->join('blocks as b', 'b.id', '=', 'u.block_id')
            ->where('p.building_id', $buildingId)->whereIn('p.user_id', $userIds)->whereNull('p.to_date')
            ->get(['p.user_id', 'p.relation', 'b.name as block', 'u.number'])
            ->groupBy('user_id')
            ->map(fn ($g) => $g->map(fn ($r) => $r->block.' / '.$r->number.($r->relation === 'tenant' ? ' (مستأجر)' : ' (مالک)'))->all())
            ->all();
    }

    public function deactivate(User $actor, int $userId, string $scope): void
    {
        $buildingId = $this->building();
        if ($userId === $actor->id) {
            throw new TeamException('نمی‌توانید خودتان را غیرفعال کنید.');
        }

        $rows = $this->rows($buildingId, $userId, $scope);
        $this->assertCanTouch($rows->pluck('key')->all());
        if (! $rows->contains('status', 'active')) {
            throw new TeamException('این شخص فعال نیست.');
        }
        $this->assertNotLastOwner($buildingId, $userId, $rows->where('status', 'active')->pluck('key')->all());

        BuildingUserRole::where(['building_id' => $buildingId, 'user_id' => $userId, 'status' => 'active'])
            ->whereIn('role_id', $this->roleIds(self::rolesOf($scope, $buildingId)))->update(['status' => 'inactive']);
    }

    public function activate(User $actor, int $userId, string $scope): void
    {
        $buildingId = $this->building();
        $rows = $this->rows($buildingId, $userId, $scope);
        $this->assertCanTouch($rows->pluck('key')->all());
        if (! $rows->contains('status', 'inactive')) {
            throw new TeamException('این شخص غیرفعال نیست.');
        }

        BuildingUserRole::where(['building_id' => $buildingId, 'user_id' => $userId, 'status' => 'inactive'])
            ->whereIn('role_id', $this->roleIds(self::rolesOf($scope)))->update(['status' => 'active']);
    }

    /** لغو دعوتِ هنوزپذیرفته‌نشده؛ تنها موردی که ردیف واقعاً حذف می‌شود. */
    public function cancelInvitation(User $actor, int $userId, string $scope): void
    {
        $buildingId = $this->building();
        $rows = $this->rows($buildingId, $userId, $scope)->where('status', 'invited');
        if ($rows->isEmpty()) {
            throw new TeamException('دعوت در انتظاری برای این شخص وجود ندارد.');
        }
        $this->assertCanTouch($rows->pluck('key')->all());

        $roleIds = $this->roleIds(self::rolesOf($scope));
        DB::transaction(function () use ($buildingId, $userId, $roleIds) {
            BuildingUserRole::where(['building_id' => $buildingId, 'user_id' => $userId, 'status' => 'invited'])->whereIn('role_id', $roleIds)->get()->each(fn (BuildingUserRole $r) => $r->delete());
            Invitation::where('user_id', $userId)->whereNull('accepted_at')->whereIn('role_id', $roleIds)->get()->each(fn (Invitation $i) => $i->delete());
        });
    }

    /**
     * تغییر نوع عضویت یک عضو فعال از مالک واحد به مستأجر یا برعکس.
     * نقش‌های پرسنلی و عضویت‌های ساختمان‌های دیگر دست‌نخورده می‌مانند.
     */
    public function changeMemberRole(User $actor, int $userId, string $newRole): void
    {
        $buildingId = $this->building();
        if ($userId === $actor->id) {
            throw new TeamException('نوع عضویت خودتان را نمی‌توانید از این بخش تغییر دهید.');
        }
        if (! in_array($newRole, self::MEMBERS, true)) {
            throw new TeamException('نوع عضویت انتخاب‌شده معتبر نیست.');
        }

        $rows = $this->rows($buildingId, $userId, 'members');
        if ($rows->isEmpty()) {
            throw new TeamException('عضویت این شخص در ساختمان پیدا نشد.');
        }
        if ($rows->count() !== 1) {
            throw new TeamException('این شخص بیش از یک نوع عضویت دارد؛ برای جلوگیری از حذف نقش دیگر، تغییر خودکار ممکن نیست.');
        }

        $current = $rows->first();
        if ($current->status !== 'active') {
            throw new TeamException('فقط اعضای فعال قابل تغییر هستند؛ برای عضویت ردشده یا در انتظار، دعوت جدید ارسال کنید.');
        }
        if ($current->key === $newRole) {
            throw new TeamException('این شخص همین حالا همین نوع عضویت را دارد.');
        }

        $targetRoleId = Role::where('key', $newRole)->value('id');
        $existingTarget = BuildingUserRole::where([
            'building_id' => $buildingId,
            'user_id' => $userId,
            'role_id' => $targetRoleId,
        ])->first();

        if ($existingTarget) {
            throw new TeamException('برای این نوع عضویت سابقه‌ای وجود دارد؛ برای جلوگیری از فعال‌سازی ناخواسته، ابتدا وضعیت آن را بررسی و دعوت جدید ارسال کنید.');
        }

        DB::transaction(function () use ($buildingId, $userId, $actor, $current, $targetRoleId) {
            BuildingUserRole::create([
                'building_id' => $buildingId,
                'user_id' => $userId,
                'role_id' => $targetRoleId,
                'status' => 'active',
                'invited_by' => $actor->id,
                'invited_at' => now(),
                'joined_at' => now(),
            ]);

            BuildingUserRole::where('id', $current->id)
                ->where('building_id', $buildingId)
                ->where('user_id', $userId)
                ->get()->each(fn (BuildingUserRole $r) => $r->delete());
        });
    }

    /** مجموعه‌ی نقش‌های پرسنلی یک نفر را برابر $roleKeys می‌کند (افزودن و حذف). */
    public function updateRoles(User $actor, int $userId, array $roleKeys): void
    {
        $buildingId = $this->building();
        if ($userId === $actor->id) {
            throw new TeamException('نقش‌های خودتان را نمی‌توانید تغییر دهید.');
        }
        $roleKeys = array_values(array_unique($roleKeys));
        $availableStaffKeys = self::rolesOf('staff', $buildingId);
        if (array_diff($roleKeys, $availableStaffKeys)) {
            throw new TeamException('نقش نامعتبر است.');
        }

        $rows = $this->rows($buildingId, $userId, 'staff');
        $current = $rows->pluck('key')->all();
        $toAdd = array_values(array_diff($roleKeys, $current));
        $toRemove = array_values(array_diff($current, $roleKeys));
        $this->assertCanTouch(array_merge($current, $toAdd));

        if (in_array('owner', $toRemove, true) && $rows->where('key', 'owner')->contains('status', 'active')) {
            $this->assertNotLastOwner($buildingId, $userId, ['owner']);
        }

        // شخصی که همین حالا عضو فعال است، نقش تازه را هم فعال می‌گیرد؛ در غیر این صورت منتظر پذیرش دعوت می‌ماند.
        $joined = BuildingUserRole::where(['building_id' => $buildingId, 'user_id' => $userId, 'status' => 'active'])->exists();

        DB::transaction(function () use ($buildingId, $userId, $actor, $toAdd, $toRemove, $joined) {
            $user = User::findOrFail($userId);

            // If the person has not joined yet, a new role must create a real invitation,
            // including a fresh token and SMS; changing the membership row alone is not enough.
            if (! $joined && $toAdd) {
                foreach ($toAdd as $key) {
                    app(InvitationService::class)->invite($user->mobile, $key, $actor);
                }
            } else {
                foreach ($toAdd as $key) {
                    BuildingUserRole::updateOrCreate(
                        ['building_id' => $buildingId, 'user_id' => $userId, 'role_id' => (in_array($key, self::PROTECTED, true) ? Role::whereNull('building_id')->where('key', $key)->value('id') : (Role::where('building_id', $buildingId)->where('key', $key)->value('id') ?? (! Role::where('building_id', $buildingId)->where('is_staff', true)->exists() ? Role::whereNull('building_id')->where('key', $key)->value('id') : null)))],
                        ['status' => 'active', 'invited_by' => $actor->id, 'invited_at' => now(), 'joined_at' => now()],
                    );
                }
            }

            if ($toRemove) {
                $removedRoleIds = $this->roleIds($toRemove);

                // Invalidate any outstanding invitation for a role that is being removed.
                Invitation::where('building_id', $buildingId)
                    ->where('user_id', $userId)
                    ->whereIn('role_id', $removedRoleIds)
                    ->whereNull('accepted_at')
                    ->whereNull('rejected_at')
                    ->get()->each(fn ($m) => $m->delete());

                BuildingUserRole::where(['building_id' => $buildingId, 'user_id' => $userId])
                    ->whereIn('role_id', $removedRoleIds)
                    ->get()->each(fn ($m) => $m->delete());
            }
        });
    }

    // ---------- کمکی

    private function building(): int
    {
        return app(CurrentBuilding::class)->id() ?? throw new TeamException('ساختمان فعالی انتخاب نشده است.');
    }

    /** @return Collection<int, object{id:int,key:string,status:string}> */
    private function rows(int $buildingId, int $userId, string $scope): Collection
    {
        return DB::table('building_user_roles as bur')->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.building_id', $buildingId)->where('bur.user_id', $userId)->whereIn('r.key', self::rolesOf($scope, $buildingId))
            ->get(['bur.id', 'r.key', 'bur.status']);
    }

    private function roleIds(array $keys): array
    {
        $buildingId = app(CurrentBuilding::class)->id();
        return Role::whereIn('key', $keys)->where(function ($query) use ($buildingId, $keys) {
            $query->whereNull('building_id')->whereIn('key', array_values(array_intersect($keys, self::PROTECTED)));
            if ($buildingId) $query->orWhere('building_id', $buildingId);
            if ($buildingId && ! DB::table('buildings')->where('id', $buildingId)->value('staff_roles_initialized')) {
                $query->orWhereNull('building_id'); // compatibility for tests/legacy buildings created outside BuildingService
            }
        })->pluck('id')->all();
    }

    /** فقط مالک ساختمان به نقش‌های «مالک» و «مدیر» دست می‌زند. */
    private function assertCanTouch(array $keys): void
    {
        if (array_intersect($keys, self::PROTECTED) && ! app(CurrentBuilding::class)->hasRole('owner')) {
            throw new AuthorizationException('فقط مالک ساختمان می‌تواند مالک یا مدیر را مدیریت کند.');
        }
    }

    private function assertNotLastOwner(int $buildingId, int $userId, array $activeKeys): void
    {
        if (! in_array('owner', $activeKeys, true)) {
            return;
        }
        $others = BuildingUserRole::where(['building_id' => $buildingId, 'status' => 'active'])->where('user_id', '!=', $userId)
            ->whereIn('role_id', $this->roleIds(['owner']))->distinct()->count('user_id');

        if ($others === 0) {
            throw new TeamException('ساختمان باید همیشه حداقل یک مالک فعال داشته باشد.');
        }
    }
}
