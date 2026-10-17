<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\UnitParty;
use Illuminate\Http\Request;

/**
 * نمای یک‌جای واحدهای مرتبط با کاربر؛ تمام داده‌ها در محدوده‌ی ساختمان فعال می‌مانند.
 */
class MyUnitController extends Controller
{
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

        // صورت‌حساب‌های متعلق به خود کاربر، حتی در صورت پایان عضویت، تاریخچه را حفظ می‌کنند.
        $invoiceUnitIds = Invoice::query()
            ->where('payer_user_id', $user->id)
            ->pluck('unit_id');

        $unitIds = $partyUnitIds->merge($invoiceUnitIds)->unique()->values();

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
