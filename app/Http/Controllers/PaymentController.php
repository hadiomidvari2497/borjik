<?php

namespace App\Http\Controllers;

use App\Models\FileLink;
use App\Models\PaymentReceipt;
use App\Services\PaymentService;
use DomainException;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->input('status'), ['pending', 'approved', 'rejected'], true) ? $request->input('status') : 'pending';

        $receipts = PaymentReceipt::with(['unit.block', 'user'])->where('status', $status)->orderByDesc('id')->paginate(20)->withQueryString();

        return view('payments.index', [
            'receipts' => $receipts,
            'status' => $status,
            'files' => FileLink::where('attachable_type', PaymentService::FILE_TYPE)->whereIn('attachable_id', $receipts->pluck('id'))->pluck('file_id', 'attachable_id'),
            'counts' => PaymentReceipt::selectRaw('status, count(*) as c')->groupBy('status')->get()->mapWithKeys(fn ($r) => [$r->status->value => (int) $r->c]),
        ]);
    }

    public function approve(Request $request, int $id, PaymentService $service)
    {
        try {
            $service->approve(PaymentReceipt::findOrFail($id), $request->user());
        } catch (DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return back()->with('status', 'فیش تأیید و به صورت‌حساب‌ها اختصاص داده شد.');
    }

    public function reject(Request $request, int $id, PaymentService $service)
    {
        $reason = $request->validate(['reason' => ['required', 'string', 'max:255']], ['reason.required' => 'دلیل رد فیش را بنویسید.'])['reason'];

        try {
            $service->reject(PaymentReceipt::findOrFail($id), $request->user(), $reason);
        } catch (DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return back()->with('status', 'فیش رد شد و به کاربر اطلاع داده شد.');
    }
}
