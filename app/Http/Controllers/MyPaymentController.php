<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\Invoice;
use App\Models\PaymentReceipt;
use App\Models\Unit;
use App\Models\UnitParty;
use App\Services\PaymentService;
use App\Support\JalaliDate;
use DomainException;
use Illuminate\Http\Request;

class MyPaymentController extends Controller
{
    use NormalizesNumbers;

    public function index(Request $request)
    {
        $units = $this->myUnits($request);

        return view('my.payments', [
            'units' => $units,
            'debts' => Invoice::whereIn('unit_id', $units->pluck('id'))->whereIn('status', ['unpaid', 'partial'])
                ->selectRaw('unit_id, sum(amount - paid_amount) as s')->groupBy('unit_id')->pluck('s', 'unit_id'),
            'receipts' => PaymentReceipt::where('user_id', $request->user()->id)->with('unit.block')->orderByDesc('id')->paginate(15),
        ]);
    }

    public function store(Request $request, PaymentService $service)
    {
        $this->normalizeNumbers($request, ['amount', 'unit_id']);
        $allowed = $this->myUnits($request)->pluck('id')->all();

        $v = $request->validate([
            'unit_id' => ['required', 'integer', 'in:'.implode(',', $allowed ?: [0])],
            'amount' => ['required', 'integer', 'min:1', 'max:99999999999'],
            'paid_at' => ['required', 'string'],
            'note' => ['nullable', 'string', 'max:500'],
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], ['unit_id.in' => 'این واحد متعلق به شما نیست.', 'receipt.required' => 'تصویر فیش را بارگذاری کنید.', 'receipt.mimes' => 'فقط تصویر (jpg، png، webp) یا pdf پذیرفته می‌شود.', 'receipt.max' => 'حجم فیش نباید بیشتر از ۵ مگابایت باشد.']);

        $paidAt = JalaliDate::parse($v['paid_at']);
        if (! $paidAt || $paidAt->isFuture()) {
            return back()->withErrors(['paid_at' => 'تاریخ پرداخت معتبر نیست (نمونه: ۱۴۰۵/۰۷/۱۶) و نباید در آینده باشد.'])->withInput();
        }

        try {
            $service->submit($request->user(), Unit::findOrFail($v['unit_id']), (int) $v['amount'], $paidAt, $v['note'] ?? null, $request->file('receipt'));
        } catch (DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage()])->withInput();
        }

        return redirect()->route('my.payments')->with('status', 'فیش ثبت شد و پس از تأیید مدیر در وضعیت مالی شما اعمال می‌شود.');
    }

    /** واحدهایی که کاربر مالک یا مستأجر فعلی آن‌هاست یا صورت‌حساب پرداخت‌نشده‌ی او دارند. */
    private function myUnits(Request $request)
    {
        $ids = UnitParty::where('user_id', $request->user()->id)->whereNull('to_date')->pluck('unit_id')
            ->merge(Invoice::where('payer_user_id', $request->user()->id)->pluck('unit_id'))->unique()->values();

        return Unit::with('block')->whereIn('id', $ids)->orderBy('block_id')->orderBy('number')->get();
    }
}
