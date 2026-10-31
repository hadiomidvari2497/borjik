<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\UnitParty;
use App\Support\CurrentBuilding;
use App\Support\JalaliDate;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

/**
 * نمای یک‌جای واحدهای مرتبط با کاربر؛ تمام داده‌ها در محدوده‌ی ساختمان فعال می‌مانند.
 */
class MyUnitController extends Controller
{

    public function editOccupancy(int $unit)
    {
        return view('my.occupancy', ['unit' => $this->ownedUnit($unit)->load('block')]);
    }

    public function updateOccupancy(Request $request, int $unit)
    {
        $record = $this->ownedUnit($unit);
        $request->merge([
            'representative_mobile' => \App\Support\MobileNumber::toLatinDigits((string) $request->input('representative_mobile')),
            'family_count' => \App\Support\MobileNumber::toLatinDigits((string) $request->input('family_count')),
        ]);
        $data = $request->validate([
            'occupancy_type' => ['required', Rule::in(['owner', 'rented', 'vacant'])],
            'representative_name' => ['nullable', 'string', 'max:150'],
            'representative_mobile' => ['nullable', 'string', 'regex:/^09[0-9]{9}$/'],
            'family_count' => ['required', 'integer', 'between:0,99'],
            'lease_start_date' => ['nullable', 'string', 'max:20'],
            'lease_end_date' => ['nullable', 'string', 'max:20'],
        ]);
        if ($data['occupancy_type'] === 'owner') {
            $data['representative_name'] = $request->user()->display_name ?: $request->user()->mobile;
            $data['representative_mobile'] = $request->user()->mobile;
            $data['lease_start_date'] = $data['lease_end_date'] = null;
        } elseif ($data['occupancy_type'] === 'rented') {
            if (blank($data['representative_name']) || blank($data['representative_mobile'])) {
                return back()->withErrors(['representative_name' => 'نام و شماره موبایل مستأجر الزامی است.'])->withInput();
            }
            $start = filled($data['lease_start_date']) ? JalaliDate::parse($data['lease_start_date']) : null;
            $end = filled($data['lease_end_date']) ? JalaliDate::parse($data['lease_end_date']) : null;
            if (! $start || ! $end || $end->lte($start)) {
                return back()->withErrors(['lease_end_date' => 'تاریخ شروع و پایان اجاره معتبر نیست؛ پایان باید بعد از شروع باشد.'])->withInput();
            }
            $data['lease_start_date'] = $start->toDateString();
            $data['lease_end_date'] = $end->toDateString();
        } else {
            $data['representative_name'] = $data['representative_mobile'] = null;
            $data['family_count'] = 0;
            $data['lease_start_date'] = $data['lease_end_date'] = null;
        }
        $data['residents_count'] = (int) $data['family_count'];
        $record->update($data);

        return redirect()->route('my.units')->with('status', 'وضعیت سکونت و اطلاعات سرپرست واحد ذخیره شد.');
    }

    private function ownedUnit(int $unit): Unit
    {
        $buildingId = app(CurrentBuilding::class)->id();
        abort_unless($buildingId, 404);

        return Unit::query()->where('building_id', $buildingId)->whereKey($unit)
            ->whereHas('parties', fn ($query) => $query->where('user_id', auth()->id())->where('relation', 'owner')->whereNull('to_date'))
            ->firstOrFail();
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $partyUnitIds = UnitParty::query()
            ->where('user_id', $user->id)
            ->whereNull('to_date')
            ->pluck('unit_id');

        $ownedUnitIds = UnitParty::query()
            ->where('user_id', $user->id)
            ->where('relation', 'owner')
            ->whereNull('to_date')
            ->pluck('unit_id');

        // کارت جزئیات فقط برای واحدهایی نمایش داده می‌شود که ارتباط کاربر با آن‌ها هنوز فعال است.
        // صورت‌حساب‌های تاریخی جداگانه پایین صفحه باقی می‌مانند، بدون افشای اطلاعات فعلی واحد قبلی.
        $unitIds = $partyUnitIds->unique()->values();

        $units = Unit::query()
            ->whereIn('id', $unitIds)
            ->with([
                'block',
                'activeParties.user',
                'parkingAssignments' => fn ($q) => $q->whereNull('released_at')->with('parking.block'),
                'storageAssignments' => fn ($q) => $q->whereNull('released_at')->with('storage.block'),
            ])
            ->orderBy('block_id')
            ->orderByRaw('cast(number as unsigned), number')
            ->get();

        $invoices = Invoice::query()
            ->where('payer_user_id', $user->id)
            ->with(['unit.block', 'period'])
            ->orderByDesc('due_date')
            ->orderByDesc('id')
            ->get();

        $contracts = collect();
        if ($user->hasPermission('contract.view.own')) {
            $contracts = Contract::query()
                ->with('unit.block')
                ->where(function ($query) use ($user, $ownedUnitIds) {
                    $query->where('user_id', $user->id)
                        ->orWhere(fn ($owned) => $owned
                            ->where('type', 'rent')
                            ->whereIn('unit_id', $ownedUnitIds));
                })
                ->orderByDesc('id')
                ->get()
                ->groupBy('unit_id');
        }

        $openInvoices = $invoices->filter(fn ($invoice) => in_array($invoice->status->value, ['unpaid', 'partial'], true));
        $debt = (int) $openInvoices->sum(fn ($invoice) => max(0, (int) $invoice->amount - (int) $invoice->paid_amount));
        $overdueCount = $openInvoices->filter(fn ($invoice) => $invoice->due_date && $invoice->due_date->isPast())->count();

        return view('my.units', [
            'units' => $units,
            'invoices' => $invoices,
            'contractsByUnit' => $contracts,
            'debt' => $debt,
            'overdueCount' => $overdueCount,
            'openInvoiceCount' => $openInvoices->count(),
            'uid' => (int) $user->id,
        ]);
    }
}
