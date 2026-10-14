<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\Building;
use App\Models\Unit;
use App\Services\AmenityService;
use App\Support\CurrentBuilding;
use App\Support\MobileNumber;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** پایه‌ی مشترک پارکینگ و انباری؛ زیرکلاس‌ها فقط cfg() را تعریف می‌کنند. */
abstract class AmenityController extends Controller
{
    /** @return array{model: class-string<Model>, assignment: class-string<Model>, fk: string, open: string, prefix: string, title: string, plural: string, declared: string} */
    abstract protected function cfg(): array;

    public function index(Request $request)
    {
        $c = $this->cfg();
        $m = $c['model'];

        $items = $m::with(['block', 'openAssignment.unit.block'])
            ->when($request->filled('block_id'), fn ($q) => $q->where('block_id', $request->integer('block_id')))
            ->when(in_array($request->input('status'), ['free', 'assigned'], true), fn ($q) => $q->where('status', $request->input('status')))
            ->orderBy('block_id')->orderByRaw('cast(number as unsigned), number')
            ->paginate(25)->withQueryString();

        return view('amenities.index', [
            'cfg' => $c,
            'items' => $items,
            'blocks' => Block::orderBy('number')->get(),
            'units' => Unit::with('block')->orderBy('block_id')->orderByRaw('cast(number as unsigned), number')->get(),
            'stats' => ['total' => $m::count(), 'assigned' => $m::where('status', 'assigned')->count()],
            'declared' => (int) Building::find(app(CurrentBuilding::class)->id())->{$c['declared']},
        ]);
    }

    public function create()
    {
        return view('amenities.form', ['cfg' => $this->cfg(), 'item' => null, 'blocks' => Block::orderBy('number')->get()]);
    }

    public function store(Request $request)
    {
        $c = $this->cfg();
        $c['model']::create($this->validated($request) + ['status' => 'free']);

        return redirect()->route($c['prefix'].'.index')->with('status', "{$c['title']} ثبت شد.");
    }

    public function bulkCreate()
    {
        return view('amenities.bulk', ['cfg' => $this->cfg(), 'blocks' => Block::orderBy('number')->get()]);
    }

    public function bulkStore(Request $request)
    {
        $c = $this->cfg();
        $request->merge(['start_number' => MobileNumber::toLatinDigits((string) $request->input('start_number'))]);

        $v = $request->validate([
            'block_id' => ['nullable', Rule::exists('blocks', 'id')->where('building_id', app(CurrentBuilding::class)->id())],
            'count' => ['required', 'integer', 'between:1,500'],
            'start_number' => ['required', 'integer', 'between:1,99999'],
            'floor' => ['nullable', 'integer', 'between:-5,200'],
        ]);

        $blockId = $v['block_id'] ?? null;
        $numbers = array_map('strval', range((int) $v['start_number'], (int) $v['start_number'] + (int) $v['count'] - 1));
        $taken = $c['model']::whereIn('number', $numbers)
            ->where(fn ($q) => $blockId === null ? $q->whereNull('block_id') : $q->where('block_id', $blockId))->pluck('number')->all();

        if ($taken) {
            return back()->withErrors(['general' => 'این شماره‌ها قبلاً ثبت شده‌اند: '.implode('، ', array_slice($taken, 0, 8))])->withInput();
        }

        DB::transaction(function () use ($c, $numbers, $blockId, $v) {
            foreach ($numbers as $n) {
                $c['model']::create(['block_id' => $blockId, 'number' => $n, 'floor' => $v['floor'] ?? null, 'status' => 'free']);
            }
        });

        return redirect()->route($c['prefix'].'.index')->with('status', count($numbers)." {$c['title']} ثبت شد.");
    }

    public function edit(int $id)
    {
        $c = $this->cfg();

        return view('amenities.form', ['cfg' => $c, 'item' => $c['model']::findOrFail($id), 'blocks' => Block::orderBy('number')->get()]);
    }

    public function update(Request $request, int $id)
    {
        $c = $this->cfg();
        $item = $c['model']::findOrFail($id);
        $item->update($this->validated($request, $item));

        return redirect()->route($c['prefix'].'.index')->with('status', "{$c['title']} ذخیره شد.");
    }

    /** حذف فقط وقتی هرگز به واحدی اختصاص داده نشده است (تاریخچه‌ی اختصاص باید بماند). */
    public function destroy(int $id)
    {
        $c = $this->cfg();
        $item = $c['model']::findOrFail($id);

        if ($c['assignment']::where($c['fk'], $item->id)->exists()) {
            return back()->withErrors(['general' => "این {$c['title']} سابقه‌ی اختصاص به واحد دارد و قابل حذف نیست."]);
        }

        $item->delete();

        return redirect()->route($c['prefix'].'.index')->with('status', "{$c['title']} حذف شد.");
    }

    public function assign(Request $request, int $id, AmenityService $service)
    {
        $c = $this->cfg();
        $item = $c['model']::findOrFail($id);
        $unitId = $request->validate([
            'unit_id' => ['required', Rule::exists('units', 'id')->where('building_id', app(CurrentBuilding::class)->id())->whereNull('deleted_at')],
        ])['unit_id'];

        try {
            $service->assign($c, $item, Unit::findOrFail($unitId), $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return back()->with('status', "{$c['title']} {$item->number} اختصاص داده شد.");
    }

    public function release(int $id, AmenityService $service)
    {
        $c = $this->cfg();

        try {
            $service->release($c, $c['model']::findOrFail($id));
        } catch (DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return back()->with('status', "{$c['title']} آزاد شد.");
    }

    private function validated(Request $request, ?Model $item = null): array
    {
        $c = $this->cfg();
        $request->merge(['number' => MobileNumber::toLatinDigits(trim((string) $request->input('number')))]);

        $data = $request->validate([
            'block_id' => ['nullable', Rule::exists('blocks', 'id')->where('building_id', app(CurrentBuilding::class)->id())],
            'number' => ['required', 'string', 'max:20'],
            'floor' => ['nullable', 'integer', 'between:-5,200'],
        ]);
        $data['block_id'] = $data['block_id'] ?? null;
        $data['floor'] = $data['floor'] ?? null;

        // ایندکس یکتای دیتابیس برای block_id خالی کار نمی‌کند (NULL ها برابر حساب نمی‌شوند)، پس اینجا هم چک می‌کنیم.
        $taken = $c['model']::where('number', $data['number'])
            ->where(fn ($q) => $data['block_id'] === null ? $q->whereNull('block_id') : $q->where('block_id', $data['block_id']))
            ->when($item, fn ($q) => $q->where('id', '!=', $item->id))->exists();

        if ($taken) {
            throw ValidationException::withMessages(['number' => "این شماره‌ی {$c['title']} قبلاً در همین محل ثبت شده است."]);
        }

        return $data;
    }
}
