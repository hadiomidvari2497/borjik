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

    /** ساخت گروهی واحدها با شماره‌گذاری پشت‌سرهم یا ترکیبیِ بلوک، طبقه و واحد. */
    public function bulkStore(Request $request, UnitService $service)
    {
        $buildingId = app(CurrentBuilding::class)->id();
        $request->merge(['start_number' => MobileNumber::toLatinDigits((string) $request->input('start_number'))]);

        $v = $request->validate([
            'block_id' => ['required', Rule::exists('blocks', 'id')->where('building_id', $buildingId)],
            'numbering_mode' => ['required', Rule::in(['sequential', 'structured'])],
            'count' => ['required', 'integer', 'between:1,500'],
            'start_number' => ['required_if:numbering_mode,sequential', 'nullable', 'integer', 'between:1,99999999999999999999'],
            'units_per_floor' => ['required', 'integer', 'between:1,99'],
            'start_floor' => ['required', 'integer', 'between:-5,200'],
            'area_m2' => ['required', 'numeric', 'between:1,99999'],
            'bedrooms' => ['nullable', 'integer', 'between:0,20'],
        ]);

        $block = Block::where('building_id', $buildingId)->findOrFail((int) $v['block_id']);
        $count = (int) $v['count'];
        $unitsPerFloor = (int) $v['units_per_floor'];
        $startFloor = (int) $v['start_floor'];
        $numbers = [];
        $floors = [];

        if ($v['numbering_mode'] === 'sequential') {
            $first = (int) $v['start_number'];
            if ($first + $count - 1 > 99999999999999999999) {
                return back()->withErrors(['start_number' => 'بازه شماره‌ها بیش از حد مجاز است.'])->withInput();
            }
            $numbers = array_map('strval', range($first, $first + $count - 1));
            foreach (range(0, $count - 1) as $i) {
                $floors[] = $startFloor + intdiv($i, $unitsPerFloor);
            }
        } else {
            $floorCount = (int) $block->floors_count;
            if ($startFloor < 1 || $startFloor > $floorCount) {
                return back()->withErrors(['start_floor' => 'در شماره‌گذاری ترکیبی، طبقه شروع باید بین ۱ و تعداد طبقات همین بلوک باشد.'])->withInput();
            }
            $capacity = $unitsPerFloor * ($floorCount - $startFloor + 1);
            if ($count > $capacity) {
                return back()->withErrors(['count' => "با توجه به تعداد طبقات بلوک، حداکثر {$capacity} واحد با این تنظیمات قابل ساخت است."])->withInput();
            }

            // Segment widths are derived from the building dimensions so two-digit block/floor/unit
            // values remain unambiguous (e.g. block 01, floor 02, unit 04 => 010204).
            $blockWidth = max(1, strlen((string) Block::where('building_id', $buildingId)->max('number')));
            $floorWidth = max(1, strlen((string) $floorCount));
            $unitWidth = max(1, strlen((string) $unitsPerFloor));
            foreach (range(0, $count - 1) as $i) {
                $floor = $startFloor + intdiv($i, $unitsPerFloor);
                $unitInFloor = ($i % $unitsPerFloor) + 1;
                $numbers[] = str_pad((string) $block->number, $blockWidth, '0', STR_PAD_LEFT)
                    .str_pad((string) $floor, $floorWidth, '0', STR_PAD_LEFT)
                    .str_pad((string) $unitInFloor, $unitWidth, '0', STR_PAD_LEFT);
                $floors[] = $floor;
            }
        }

        $taken = Unit::where('block_id', $block->id)->whereIn('number', $numbers)->pluck('number')->all();
        if ($taken) {
            return back()->withErrors(['general' => 'این شماره‌ها در بلوک قبلاً ثبت شده‌اند: '.implode('، ', array_slice($taken, 0, 8))])->withInput();
        }

        $rows = [];
        foreach ($numbers as $i => $number) {
            $rows[] = [
                'block_id' => (int) $block->id, 'number' => (string) $number,
                'floor' => $floors[$i],
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
