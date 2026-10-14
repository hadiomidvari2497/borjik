<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlockController extends Controller
{
    public function index()
    {
        return view('blocks.index', ['blocks' => Block::withCount('units')->orderBy('number')->get()]);
    }

    public function create()
    {
        return view('blocks.form', ['block' => null]);
    }

    public function store(Request $request)
    {
        Block::create($this->validated($request));

        return redirect()->route('blocks.index')->with('status', 'بلوک ساخته شد.');
    }

    public function edit(int $block)
    {
        $block = Block::findOrFail($block);

        return view('blocks.form', ['block' => $block]);
    }

    public function update(Request $request, int $block)
    {
        $block = Block::findOrFail($block);
        $block->update($this->validated($request, $block));

        return redirect()->route('blocks.index')->with('status', 'بلوک ذخیره شد.');
    }

    /** حذف فقط وقتی بلوک واحد، پارکینگ یا انباری ندارد (بند ۵ سند). */
    public function destroy(int $block)
    {
        $block = Block::findOrFail($block);

        if ($block->units()->exists() || $block->parkings()->exists() || $block->storages()->exists()) {
            return back()->withErrors(['general' => 'این بلوک واحد، پارکینگ یا انباری دارد و قابل حذف نیست.']);
        }

        $block->forceDelete();

        return redirect()->route('blocks.index')->with('status', 'بلوک حذف شد.');
    }

    private function validated(Request $request, ?Block $block = null): array
    {
        $buildingId = app(CurrentBuilding::class)->id();

        return $request->validate([
            'number' => ['required', 'integer', 'between:1,999', Rule::unique('blocks', 'number')->where('building_id', $buildingId)->ignore($block?->id)],
            'name' => ['required', 'string', 'max:100'],
            'floors_count' => ['required', 'integer', 'between:1,200'],
        ], ['number.unique' => 'شماره‌ی این بلوک قبلاً ثبت شده است.']);
    }
}
