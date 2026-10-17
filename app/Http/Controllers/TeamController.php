<?php

namespace App\Http\Controllers;

use App\Exceptions\TeamException;
use App\Models\Role;
use App\Services\TeamService;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function staff(Request $request, TeamService $team)
    {
        return $this->show('staff', $request, $team);
    }

    public function members(Request $request, TeamService $team)
    {
        return $this->show('members', $request, $team);
    }

    public function deactivate(Request $request, int $userId, TeamService $team)
    {
        return $this->run($request, fn ($scope) => $team->deactivate($request->user(), $userId, $scope), 'غیرفعال شد.');
    }

    public function activate(Request $request, int $userId, TeamService $team)
    {
        return $this->run($request, fn ($scope) => $team->activate($request->user(), $userId, $scope), 'فعال شد.');
    }

    public function cancelInvitation(Request $request, int $userId, TeamService $team)
    {
        return $this->run($request, fn ($scope) => $team->cancelInvitation($request->user(), $userId, $scope), 'دعوت لغو شد.');
    }

    public function changeMemberRole(Request $request, int $userId, TeamService $team)
    {
        abort_unless($request->user()->can('member.manage'), 403);
        $data = $request->validate(['role' => ['required', 'in:unit_owner,tenant']]);

        try {
            $team->changeMemberRole($request->user(), $userId, $data['role']);
        } catch (TeamException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return back()->with('status', 'نوع عضویت عضو تغییر کرد.');
    }

    public function updateRoles(Request $request, int $userId, TeamService $team)
    {
        $roles = $request->validate(['roles' => ['nullable', 'array'], 'roles.*' => ['string']])['roles'] ?? [];

        try {
            $team->updateRoles($request->user(), $userId, $roles);
        } catch (TeamException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return back()->with('status', 'نقش‌ها ذخیره شد.');
    }

    private function show(string $scope, Request $request, TeamService $team)
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $isOwner = app(CurrentBuilding::class)->hasRole('owner');
        $people = $team->list($buildingId, $scope);

        // مالک و مدیر را فقط مالک ساختمان مدیریت می‌کند؛ خود کاربر هم هرگز دستکاری نمی‌شود.
        $people = $people->map(fn ($p) => $p + [
            'is_self' => $p['user_id'] === $request->user()->id,
            'locked' => $p['user_id'] === $request->user()->id
                || (! $isOwner && array_intersect(array_keys($p['roles']), ['owner', 'manager'])),
        ]);

        $invitable = $scope === 'staff'
            ? ($isOwner ? TeamService::STAFF : ['security', 'cleaning', 'maintenance'])
            : TeamService::MEMBERS;

        return view('team.index', [
            'scope' => $scope,
            'people' => $people,
            'roleNames' => Role::whereIn('key', TeamService::rolesOf($scope))->pluck('name_fa', 'key'),
            'invitable' => $invitable,
            'editable' => $scope === 'staff' ? ($isOwner ? TeamService::STAFF : ['security', 'cleaning', 'maintenance']) : [],
            'units' => $scope === 'members' ? $team->unitLabels($buildingId, $people->pluck('user_id')->all()) : [],
        ]);
    }

    /** مجوز گروه (پرسنل یا اعضا) را از فرم می‌خواند، بررسی می‌کند و اکشن را اجرا می‌کند. */
    private function run(Request $request, callable $action, string $success)
    {
        $scope = $request->input('scope') === 'members' ? 'members' : 'staff';
        abort_unless($request->user()->can($scope === 'staff' ? 'staff.manage' : 'member.manage'), 403);

        try {
            $action($scope);
        } catch (TeamException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return back()->with('status', $success);
    }
}
