<?php

namespace App\Http\Controllers;

use App\Models\SecurityShift;
use App\Models\User;
use App\Support\CurrentBuilding;
use App\Support\JalaliDate;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SecurityShiftController extends Controller
{
    public function index(Request $request)
    {
        $buildingId = app(CurrentBuilding::class)->id();
        abort_unless($buildingId, 403);

        $canManage = $request->user()->can('shift.manage');
        $shifts = SecurityShift::query()
            ->with('user')
            ->when(! $canManage, fn ($query) => $query->where('user_id', $request->user()->id))
            ->orderByRaw('shift_date IS NULL')
            ->orderBy('shift_date')
            ->orderBy('weekday')
            ->orderBy('start_time')
            ->get();

        $guards = $canManage
            ? User::query()
                ->whereHas('buildingRoles', fn ($query) => $query
                    ->where('building_id', $buildingId)
                    ->where('status', 'active')
                    ->whereHas('role', fn ($role) => $role->where('key', 'security')))
                ->orderBy('first_name')->orderBy('last_name')->get()
            : collect();

        return view('shifts.index', [
            'shifts' => $shifts,
            'guards' => $guards,
            'canManage' => $canManage,
            'editingShift' => null,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->can('shift.manage'), 403);
        $data = $this->validated($request);
        $buildingId = app(CurrentBuilding::class)->id();

        $this->ensureGuardBelongsToBuilding((int) $data['user_id'], (int) $buildingId);

        SecurityShift::create([
            'building_id' => $buildingId,
            'user_id' => $data['user_id'],
            'location' => filled($data['location'] ?? null) ? trim($data['location']) : null,
            'shift_date' => $data['shift_date'],
            'weekday' => $data['weekday'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        return redirect()->route('shifts.index')->with('status', 'شیفت حراست ثبت شد.');
    }

    public function edit(Request $request, SecurityShift $shift)
    {
        abort_unless($request->user()->can('shift.manage'), 403);

        return view('shifts.index', $this->indexData($request) + ['editingShift' => $shift]);
    }

    public function update(Request $request, SecurityShift $shift)
    {
        abort_unless($request->user()->can('shift.manage'), 403);
        $data = $this->validated($request);
        $buildingId = app(CurrentBuilding::class)->id();

        $this->ensureGuardBelongsToBuilding((int) $data['user_id'], (int) $buildingId);

        $shift->update([
            'user_id' => $data['user_id'],
            'location' => filled($data['location'] ?? null) ? trim($data['location']) : null,
            'shift_date' => $data['shift_date'],
            'weekday' => $data['weekday'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        return redirect()->route('shifts.index')->with('status', 'شیفت حراست ویرایش شد.');
    }

    public function destroy(Request $request, SecurityShift $shift)
    {
        abort_unless($request->user()->can('shift.manage'), 403);
        $shift->delete();

        return redirect()->route('shifts.index')->with('status', 'شیفت حذف شد.');
    }

    private function indexData(Request $request): array
    {
        $buildingId = app(CurrentBuilding::class)->id();

        return [
            'shifts' => SecurityShift::with('user')->orderByRaw('shift_date IS NULL')->orderBy('shift_date')->orderBy('weekday')->orderBy('start_time')->get(),
            'guards' => User::query()
                ->whereHas('buildingRoles', fn ($query) => $query->where('building_id', $buildingId)->where('status', 'active')
                    ->whereHas('role', fn ($role) => $role->where('key', 'security')))
                ->orderBy('first_name')->orderBy('last_name')->get(),
            'canManage' => true,
        ];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'schedule_type' => ['required', 'in:date,weekly'],
            'shift_date' => ['nullable', 'string', 'max:20'],
            'weekday' => ['nullable', 'integer', 'between:0,6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'location' => ['nullable', 'string', 'max:150'],
        ]);

        $shiftDate = null;
        $weekday = null;

        if ($data['schedule_type'] === 'date') {
            $shiftDate = JalaliDate::parse($data['shift_date'] ?? null);
            if (! $shiftDate) {
                throw ValidationException::withMessages(['shift_date' => 'تاریخ شیفت معتبر نیست؛ از تقویم شمسی انتخاب کنید.']);
            }
        } else {
            if (! isset($data['weekday']) || $data['weekday'] === '') {
                throw ValidationException::withMessages(['weekday' => 'روز تکرار هفتگی را انتخاب کنید.']);
            }
            $weekday = (int) $data['weekday'];
        }

        $data['shift_date'] = $shiftDate?->toDateString();
        $data['weekday'] = $weekday;

        return $data;
    }

    private function ensureGuardBelongsToBuilding(int $userId, int $buildingId): void
    {
        $isGuard = User::query()->whereKey($userId)->whereHas('buildingRoles', fn ($query) => $query
            ->where('building_id', $buildingId)
            ->where('status', 'active')
            ->whereHas('role', fn ($role) => $role->where('key', 'security')))
            ->exists();

        if (! $isGuard) {
            throw ValidationException::withMessages(['user_id' => 'فقط حراست فعال همین ساختمان را می‌توان به شیفت اختصاص داد.']);
        }
    }
}
