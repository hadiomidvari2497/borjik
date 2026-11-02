<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class MyInvoiceController extends Controller
{
    /** صورت‌حساب‌هایی که پرداخت‌شان بر عهده‌ی خود کاربر است. */
    public function index(Request $request)
    {
        $mine = Invoice::where('payer_user_id', $request->user()->id);

        return view('my.invoices', [
            'invoices' => (clone $mine)->with(['unit.block', 'period'])->orderByDesc('due_date')->orderByDesc('id')->paginate(20),
            'debt' => (int) (clone $mine)->whereIn('status', ['unpaid', 'partial'])->selectRaw('coalesce(sum(amount - paid_amount), 0) as s')->value('s'),
        ]);
    }

    /** جزئیات فقط برای مسئول پرداخت همان صورت‌حساب قابل مشاهده است. */
    public function show(Request $request, int $invoice)
    {
        $invoice = Invoice::query()
            ->where('payer_user_id', $request->user()->id)
            ->with(['unit.block', 'period', 'allocations.receipt'])
            ->findOrFail($invoice);

        $snapshot = $invoice->period?->settings_snapshot ?? [];
        $expenseLines = data_get($snapshot, 'expenses', []);

        return view('my.invoice-show', [
            'invoice' => $invoice,
            'expenseLines' => is_array($expenseLines) ? $expenseLines : [],
            'method' => data_get($invoice->breakdown, 'method', data_get($snapshot, 'settings.method')),
        ]);
    }

}
