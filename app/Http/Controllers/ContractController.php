<?php

namespace App\Http\Controllers;

use App\Enums\ChargePayer;
use App\Enums\ContractType;
use App\Http\Controllers\Concerns\NormalizesNumbers;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Unit;
use App\Models\UnitParty;
use App\Models\User;
use App\Services\ContractService;
use App\Support\JalaliDate;
use DomainException;
use Illuminate\Http\Request;

/** قراردادهای فروش و اجاره (بند ۴.۱۳)؛ فقط برای مالک و مدیر ساختمان (مجوز contract.manage). */
class ContractController extends Controller
{
    use NormalizesNumbers;

    private const FILE_RULE = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'];
    private const FILE_MESSAGES = ['contract_file.mimes' => 'فقط pdf یا تصویر (jpg، png، webp) پذیرفته می‌شود.', 'contract_file.max' => 'حجم فایل نباید بیشتر از ۱۰ مگابایت باشد.'];

    public function index(Request $request)
    {
        $q = Contract::with(['unit.block', 'user'])->orderByDesc('id');

        if (in_array($type = $request->query('type'), ['rent', 'sale'], true)) {
            $q->where('type', $type);
        }
        if (in_array($status = $request->query('status'), ['active', 'ended'], true)) {
            $q->where('status', $status);
        }
        if (filled($term = trim((string) $request->query('q')))) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], \App\Support\MobileNumber::toLatinDigits($term)).'%';
            $q->where(function ($w) use ($like) {
                $w->whereHas('unit', fn ($u) => $u->where('number', 'like', $like))
                    ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', $like)->orWhere('last_name', 'like', $like)
                        ->orWhere('company_name', 'like', $like)->orWhere('mobile', 'like', $like));
            });
        }

        $counts = Contract::query()->toBase()->selectRaw('type, status, count(*) as c')->groupBy('type', 'status')->get()
            ->mapWithKeys(fn ($r) => [$r->type.'.'.$r->status => (int) $r->c]);

        return view('contracts.index', ['contracts' => $q->paginate(15)->withQueryString(), 'counts' => $counts]);
    }

    public function create(Request $request, ContractService $service)
    {
        $type = $request->query('type') === 'sale' ? 'sale' : 'rent';

        $units = Unit::with('block')->orderBy('block_id')->orderBy('number')->get();
        if ($type === 'rent') {
            $busy = Contract::active()->where('type', 'rent')->pluck('unit_id');
            $units = $units->reject(fn (Unit $u) => $u->status->value === 'repair' || $busy->contains($u->id))->values();
        }

        return view('contracts.create', [
            'type' => $type,
            'units' => $units,
            'people' => $service->candidates($type === 'rent' ? 'tenant' : 'unit_owner'),
        ]);
    }

    public function store(Request $request, ContractService $service)
    {
        $isRent = $request->input('type') === 'rent';
        abort_unless($isRent || $request->input('type') === 'sale', 422);

        $this->normalizeNumbers($request, ['unit_id', 'user_id', 'deposit_amount', 'monthly_rent', 'sale_price']);
        $money = ['required', 'integer', 'max:99999999999'];

        $v = $request->validate(array_merge([
            'unit_id' => ['required', 'integer'],
            'user_id' => ['required', 'integer'],
            'signed_at' => ['required', 'string'],
            'contract_file' => self::FILE_RULE,
        ], $isRent ? [
            'deposit_amount' => array_merge($money, ['min:0']),
            'monthly_rent' => array_merge($money, ['min:1']),
            'end_date' => ['required', 'string'],
            'charge_payer' => ['required', 'in:tenant,owner'],
        ] : [
            'sale_price' => array_merge($money, ['min:1']),
        ]), self::FILE_MESSAGES + ['unit_id.required' => 'واحد را انتخاب کنید.', 'user_id.required' => $isRent ? 'مستأجر را انتخاب کنید.' : 'خریدار را انتخاب کنید.']);

        $signedAt = JalaliDate::parse($v['signed_at']);
        if (! $signedAt || $signedAt->isFuture() || $signedAt->year < 1900) {
            return back()->withErrors(['signed_at' => 'تاریخ معتبر نیست (نمونه: ۱۴۰۵/۰۷/۱۶) و نباید در آینده باشد.'])->withInput();
        }

        $unit = Unit::find($v['unit_id']);
        $person = User::find($v['user_id']);
        if (! $unit || ! $person) {
            return back()->withErrors(['general' => 'واحد یا شخص انتخاب‌شده معتبر نیست.'])->withInput();
        }

        try {
            if ($isRent) {
                $endDate = JalaliDate::parse($v['end_date']);
                if (! $endDate || $endDate->lte($signedAt)) {
                    return back()->withErrors(['end_date' => 'تاریخ پایان معتبر نیست و باید بعد از تاریخ عقد باشد.'])->withInput();
                }
                $contract = $service->registerRent($request->user(), $unit, $person, (int) $v['deposit_amount'], (int) $v['monthly_rent'], $signedAt, $endDate, ChargePayer::from($v['charge_payer']), $request->file('contract_file'));
            } else {
                $contract = $service->registerSale($request->user(), $unit, $person, (int) $v['sale_price'], $signedAt, $request->file('contract_file'));
            }
        } catch (DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage()])->withInput();
        }

        return redirect()->route('contracts.show', $contract->id)->with('status', $isRent ? 'قرارداد اجاره ثبت شد و واحد در وضعیت «اجاره‌داده‌شده» قرار گرفت.' : 'قرارداد فروش ثبت شد و مالکیت واحد منتقل شد.');
    }

    public function show(int $id)
    {
        $contract = Contract::with(['unit.block', 'user', 'createdBy'])->findOrFail($id);

        return view('contracts.show', [
            'contract' => $contract,
            'owner' => UnitParty::with('user')->where('unit_id', $contract->unit_id)->where('relation', 'owner')->whereNull('to_date')->first()?->user,
            'file' => $contract->attachedFile(),
            'invoices' => Invoice::where('contract_id', $contract->id)->orderByDesc('id')->limit(12)->get(),
        ]);
    }

    public function end(int $id, ContractService $service)
    {
        try {
            $service->endRent(Contract::findOrFail($id));
        } catch (DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return redirect()->route('contracts.show', $id)->with('status', 'قرارداد اجاره پایان یافت و مستأجر از ساکنین واحد حذف شد.');
    }

    public function payer(Request $request, int $id, ContractService $service)
    {
        $v = $request->validate(['charge_payer' => ['required', 'in:tenant,owner']], ['charge_payer.in' => 'گزینه‌ی نامعتبر است.']);

        try {
            $service->changePayer(Contract::findOrFail($id), ChargePayer::from($v['charge_payer']));
        } catch (DomainException $e) {
            return back()->withErrors(['general' => $e->getMessage()]);
        }

        return back()->with('status', 'مسئول پرداخت شارژ تغییر کرد و از دوره‌ی محاسبه‌ی بعدی اعمال می‌شود.');
    }

    public function file(Request $request, int $id, ContractService $service)
    {
        $request->validate(['contract_file' => array_merge(['required'], array_slice(self::FILE_RULE, 1))], self::FILE_MESSAGES + ['contract_file.required' => 'فایل قرارداد را انتخاب کنید.']);

        $service->attachFile(Contract::findOrFail($id), $request->file('contract_file'), $request->user());

        return back()->with('status', 'فایل قرارداد ذخیره شد.');
    }
}
