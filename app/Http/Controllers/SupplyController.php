<?php

namespace App\Http\Controllers;

use App\Models\Supply;
use App\Models\SupplyMovement;
use App\Support\CurrentBuilding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplyController extends Controller
{
    public function index()
    {
        abort_unless(app(CurrentBuilding::class)->id(), 403);

        $supplies = Supply::query()->withCount('movements')->orderBy('name')->get();
        $movements = SupplyMovement::query()->with(['supply', 'user'])->latest()->limit(30)->get();

        return view('supplies.index', [
            'supplies' => $supplies,
            'movements' => $movements,
            'lowCount' => $supplies->filter(fn ($supply) => (float) $supply->quantity <= (float) $supply->min_quantity)->count(),
            'totalItems' => $supplies->count(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:30'],
            'min_quantity' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'initial_quantity' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'reason' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $supply = Supply::create([
                'name' => trim($data['name']),
                'unit' => filled($data['unit'] ?? null) ? trim($data['unit']) : null,
                'quantity' => 0,
                'min_quantity' => $data['min_quantity'],
            ]);

            $quantity = $this->toMinorUnits($data['initial_quantity'] ?? 0);
            if ($quantity > 0) {
                $supply->quantity = $quantity / 100;
                $supply->save();
                SupplyMovement::create([
                    'supply_id' => $supply->id,
                    'delta' => $quantity / 100,
                    'reason' => filled($data['reason'] ?? null) ? trim($data['reason']) : 'موجودی اولیه',
                    'user_id' => $request->user()->id,
                ]);
            }
        });

        return redirect()->route('supplies.index')->with('status', 'قلم ملزومات ثبت شد.');
    }

    public function update(Request $request, Supply $supply)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'unit' => ['nullable', 'string', 'max:30'],
            'min_quantity' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $supply->update([
            'name' => trim($data['name']),
            'unit' => filled($data['unit'] ?? null) ? trim($data['unit']) : null,
            'min_quantity' => $data['min_quantity'],
        ]);

        return redirect()->route('supplies.index')->with('status', 'مشخصات ملزومات ویرایش شد.');
    }

    public function move(Request $request, Supply $supply)
    {
        $data = $request->validate([
            'direction' => ['required', 'in:in,out'],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'reason' => ['required', 'string', 'max:150'],
        ]);

        $updated = DB::transaction(function () use ($data, $request, $supply) {
            $locked = Supply::query()->whereKey($supply->id)->lockForUpdate()->firstOrFail();
            $amount = $this->toMinorUnits($data['quantity']);
            $current = $this->toMinorUnits($locked->quantity);
            $delta = $data['direction'] === 'in' ? $amount : -$amount;
            $next = $current + $delta;

            if ($next < 0) {
                return false;
            }

            $locked->quantity = $next / 100;
            $locked->save();

            SupplyMovement::create([
                'supply_id' => $locked->id,
                'delta' => $delta / 100,
                'reason' => trim($data['reason']),
                'user_id' => $request->user()->id,
            ]);

            return true;
        });

        if (! $updated) {
            return redirect()->route('supplies.index')->withErrors([
                'quantity' => 'موجودی کافی نیست؛ مقدار مصرف از موجودی فعلی بیشتر است.',
            ]);
        }

        return redirect()->route('supplies.index')->with('status', $data['direction'] === 'in' ? 'ورود ملزومات ثبت شد.' : 'مصرف ملزومات ثبت شد.');
    }

    public function destroy(Supply $supply)
    {
        if ($supply->movements()->exists()) {
            return redirect()->route('supplies.index')->with('error', 'این قلم گردش موجودی دارد؛ برای حفظ سابقه امکان حذف آن نیست.');
        }

        $supply->delete();

        return redirect()->route('supplies.index')->with('status', 'قلم ملزومات حذف شد.');
    }

    private function toMinorUnits(mixed $value): int
    {
        return (int) round((float) $value * 100);
    }
}
