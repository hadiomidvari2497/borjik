<?php

namespace App\Http\Controllers;

use App\Enums\Direction;
use App\Exceptions\SubscriptionException;
use App\Models\Block;
use App\Models\Building;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\ParkingAssignment;
use App\Models\StorageAssignment;
use App\Models\Unit;
use App\Models\UnitParty;
use App\Services\SubscriptionLimit;
use App\Services\BuildingService;
use App\Services\UnitFinance;
use App\Services\UnitService;
use App\Support\CurrentBuilding;
use App\Support\MobileNumber;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    public function index(Request $request, SubscriptionLimit $limit, UnitFinance $finance, BuildingService $buildingService)
    {
        $units = Unit::with('block')
            ->withCount([
                'parkingAssignments as parkings_count' => fn ($q) => $q->whereNull('released_at'),
                'storageAssignments as storages_count' => fn ($q) => $q->whereNull('released_at'),
            ])
            ->when($request->filled('block_id'), fn ($q) => $q->where('block_id', $request->integer('block_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', (string) $request->input('status')))
            ->when($request->filled('q'), fn ($q) => $q->where('number', 'like', '%'.MobileNumber::toLatinDigits($request->string('q')).'%'))
            ->orderBy('block_id')->orderByRaw('cast(number as unsigned), number')
            ->paginate(25)->withQueryString();

        $building = Building::findOrFail(app(CurrentBuilding::class)->id());
        $sub = $buildingService->ensureActiveSubscription($building);

        return view('units.index', [
            'units' => $units,
            'blocks' => Block::orderBy('number')->get(),
            'balances' => $finance->balances($units->pluck('id')->all()),
            'quota' => $sub ? ['used' => $limit->used($sub), 'limit' => $sub->units_limit, 'active' => $sub->isActive()] : null,
        ]);
    }

    public function create()
    {
        return view('units.form', ['unit' => null, 'blocks' => Block::orderBy('number')->get(), 'directions' => Direction::cases()]);
    }

    public function store(Request $request, UnitService $service)
    {
        $data = $this->validated($request);
        $data['status'] = $request->input('status') === 'repair' ? 'repair' : 'vacant';

        try {
            $service->createMany($this->building(), [$data]);
        } catch (SubscriptionException $e) {
            return back()->withErrors(['general' => $e->getMessage()])->withInput();
        }

        return redirect()->route('units.index')->with('status', 'واحد ساخته شد.');
    }

    public function bulkCreate()
    {
        return view('units.bulk', ['blocks' => Block::orderBy('number')->get()]);
    }

    /** ساخت گروهی واحدها (مثلاً ۲۰ واحد با شماره‌گذاری پشت‌سرهم و ۴ واحد در هر طبقه). */
    public function bulkStore(Request $request, UnitService $service)
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $request->merge(['start_number' => MobileNumber::toLatinDigits((string) $request->input('start_number'))]);

        $v = $request->validate([
            'block_id' => ['required', Rule::exists('blocks', 'id')->where('building_id', $buildingId)],
            'count' => ['required', 'integer', 'between:1,500'],
            'start_number' => ['required', 'integer', 'between:1,99999'],
            'units_per_floor' => ['required', 'integer', 'between:1,50'],
            'start_floor' => ['required', 'integer', 'between:-5,200'],
            'area_m2' => ['required', 'numeric', 'between:1,99999'],
            'bedrooms' => ['nullable', 'integer', 'between:0,20'],
        ]);

        $numbers = range((int) $v['start_number'], (int) $v['start_number'] + (int) $v['count'] - 1);
        $taken = Unit::where('block_id', $v['block_id'])->whereIn('number', array_map('strval', $numbers))->pluck('number')->all();
        if ($taken) {
            return back()->withErrors(['general' => 'این شماره‌ها در بلوک قبلاً ثبت شده‌اند: '.implode('، ', array_slice($taken, 0, 8))])->withInput();
        }

        $rows = [];
        foreach ($numbers as $i => $n) {
            $rows[] = [
                'block_id' => (int) $v['block_id'], 'number' => (string) $n,
                'floor' => (int) $v['start_floor'] + intdiv($i, (int) $v['units_per_floor']),
                'area_m2' => $v['area_m2'], 'bedrooms' => $v['bedrooms'] ?? null, 'status' => 'vacant',
            ];
        }

        try {
            $count = $service->createMany($this->building(), $rows);
        } catch (SubscriptionException $e) {
            return back()->withErrors(['general' => $e->getMessage()])->withInput();
        }

        return redirect()->route('units.index')->with('status', "{$count} واحد ساخته شد.");
    }

    public function edit(int $unit)
    {
        $unit = Unit::findOrFail($unit);

        return view('units.form', ['unit' => $unit, 'blocks' => Block::orderBy('number')->get(), 'directions' => Direction::cases()]);
    }

    public function update(Request $request, int $unit)
    {
        $unit = Unit::findOrFail($unit);
        $data = $this->validated($request, $unit);

        // وضعیت «فروخته‌شده» و «اجاره‌داده‌شده» فقط با ثبت قرارداد عوض می‌شود؛ دستی فقط خالی ⇄ در حال تعمیر.
        if (in_array($unit->status->value, ['vacant', 'repair'], true) && in_array($request->input('status'), ['vacant', 'repair'], true)) {
            $data['status'] = $request->input('status');
        }

        $unit->update($data);

        return redirect()->route('units.index')->with('status', 'واحد ذخیره شد.');
    }

    /** حذف فقط برای واحدی که هیچ سابقه‌ای ندارد؛ در غیر این صورت داده‌ی مالی و حقوقی از بین می‌رفت. */
    public function destroy(int $unit)
    {
        $unit = Unit::findOrFail($unit);

        $blockers = array_filter([
            UnitParty::where('unit_id', $unit->id)->exists() ? 'مالک یا مستأجر' : null,
            Contract::where('unit_id', $unit->id)->exists() ? 'قرارداد' : null,
            Invoice::where('unit_id', $unit->id)->exists() ? 'صورت‌حساب' : null,
            (ParkingAssignment::where('unit_id', $unit->id)->exists() || StorageAssignment::where('unit_id', $unit->id)->exists()) ? 'پارکینگ یا انباری' : null,
        ]);

        if ($blockers) {
            return back()->withErrors(['general' => 'این واحد سابقه‌ی '.implode('، ', $blockers).' دارد و قابل حذف نیست.']);
        }

        $unit->forceDelete();

        return redirect()->route('units.index')->with('status', 'واحد حذف شد.');
    }

    private function building(): Building
    {
        return Building::findOrFail(app(CurrentBuilding::class)->id());
    }

    private function validated(Request $request, ?Unit $unit = null): array
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $request->merge(['postal_code' => MobileNumber::toLatinDigits((string) $request->input('postal_code'))]);

        $data = $request->validate([
            'block_id' => ['required', Rule::exists('blocks', 'id')->where('building_id', $buildingId)],
            'number' => ['required', 'string', 'max:20', Rule::unique('units', 'number')
                ->where(fn ($q) => $q->where('building_id', $buildingId)->where('block_id', $request->input('block_id')))
                ->ignore($unit?->id)],
            'postal_code' => ['nullable', 'digits:10'],
            'floor' => ['required', 'integer', 'between:-5,200'],
            'area_m2' => ['required', 'numeric', 'between:1,99999'],
            'bedrooms' => ['nullable', 'integer', 'between:0,20'],
            'direction' => ['nullable', Rule::enum(Direction::class)],
            'residents_count' => ['nullable', 'integer', 'between:0,99'],
            'opening_balance' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], ['number.unique' => 'این شماره‌ی واحد در این بلوک قبلاً ثبت شده است.']);

        // واحد به‌صورت پیش‌فرض ساکن ندارد؛ تعداد ساکنین فقط برای واحدِ فروخته‌شده یا اجاره‌داده‌شده ثبت می‌شود.
        $occupied = $unit && in_array($unit->status->value, ['sold', 'rented'], true);
        $data['residents_count'] = $occupied ? (int) ($data['residents_count'] ?? 0) : 0;
        $data['opening_balance'] = (int) ($data['opening_balance'] ?? 0);

        return $data;
    }
}
