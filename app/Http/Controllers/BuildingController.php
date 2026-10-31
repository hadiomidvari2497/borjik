<?php

namespace App\Http\Controllers;

use App\Enums\BuildingType;
use App\Models\Building;
use App\Support\CurrentBuilding;
use App\Support\JalaliDate;
use App\Support\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BuildingController extends Controller
{
    /** ساختمان‌های من (همه‌ی عضویت‌های فعال کاربر). */
    public function index(Request $request)
    {
        $rows = DB::table('building_user_roles as bur')
            ->join('buildings as b', 'b.id', '=', 'bur.building_id')
            ->join('roles as r', 'r.id', '=', 'bur.role_id')
            ->where('bur.user_id', $request->user()->id)->where('bur.status', 'active')->whereNull('b.deleted_at')
            ->orderBy('b.name')->get(['b.id', 'b.name', 'b.code', 'r.name_fa']);

        $unitCounts = DB::table('units')->whereIn('building_id', $rows->pluck('id')->unique())->whereNull('deleted_at')
            ->selectRaw('building_id, count(*) as c')->groupBy('building_id')->pluck('c', 'building_id');

        $buildings = $rows->groupBy('id')->map(fn ($g) => [
            'id' => $g[0]->id, 'name' => $g[0]->name, 'code' => $g[0]->code,
            'roles' => $g->pluck('name_fa')->implode('، '), 'units' => (int) ($unitCounts[$g[0]->id] ?? 0),
        ])->values();

        return view('buildings.index', [
            'buildings' => $buildings,
            'currentId' => app(CurrentBuilding::class)->id(),
            'hasSubscription' => (bool) optional(\App\Models\Building::find(app(CurrentBuilding::class)->id()))->subscription?->isActive(),
        ]);
    }

    public function create(Request $request)
    {
        return view('buildings.form', $this->formData(null) + [
            'blocked' => false,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $building = app(\App\Services\BuildingService::class)->create($request->user(), $data);

        $request->session()->put('current_building_id', $building->id);

        return redirect()->route('subscription.show')->with('status', 'ساختمان ساخته شد. برای فعال‌شدن امکانات، اشتراک همین ساختمان را انتخاب کنید. کد ساختمان: '.$building->code);
    }

    /** تنظیمات ساختمان فعال. */
    public function edit()
    {
        $building = Building::findOrFail(app(CurrentBuilding::class)->id());

        return view('buildings.form', $this->formData($building) + ['blocked' => false]);
    }

    public function update(Request $request)
    {
        $building = Building::findOrFail(app(CurrentBuilding::class)->id());
        $building->update($this->validated($request));

        return redirect()->route('building.settings')->with('status', 'تنظیمات ساختمان ذخیره شد.');
    }

    private function formData(?Building $building): array
    {
        return [
            'building' => $building,
            'types' => BuildingType::cases(),
            'provinces' => DB::table('provinces')->orderBy('name')->get(),
            'cities' => DB::table('cities')->orderBy('name')->get(['id', 'province_id', 'name'])->groupBy('province_id'),
        ];
    }

    private function validated(Request $request): array
    {
        $request->merge(['postal_code' => MobileNumber::toLatinDigits((string) $request->input('postal_code'))]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'postal_code' => ['nullable', 'digits:10'],
            'type' => ['required', Rule::enum(BuildingType::class)],
            'built_at' => ['nullable', 'string'],
            'parking_count_info' => ['nullable', 'integer', 'between:0,9999'],
            'storage_count_info' => ['nullable', 'integer', 'between:0,9999'],
            'province_id' => ['nullable', 'exists:provinces,id'],
            'city_id' => ['nullable', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:1000'],
        ]);

        $builtAt = null;
        if (filled($data['built_at'] ?? null)) {
            $builtAt = JalaliDate::parse($data['built_at']);
            if (! $builtAt) {
                throw \Illuminate\Validation\ValidationException::withMessages(['built_at' => 'تاریخ ساخت معتبر نیست (نمونه: ۱۳۹۵/۰۶/۱۵).']);
            }
        }
        $data['built_at'] = $builtAt;
        $data['parking_count_info'] = (int) ($data['parking_count_info'] ?? 0);
        $data['storage_count_info'] = (int) ($data['storage_count_info'] ?? 0);

        return $data;
    }
}
