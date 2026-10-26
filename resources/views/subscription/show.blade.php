@php $F = \App\Support\Fa::class; @endphp
@extends('layouts.app')
@section('title', 'خرید اشتراک')
@section('content')
<style>
.sub-page{--sub-ink:var(--text);--sub-muted:var(--muted);min-width:0}.sub-page *{box-sizing:border-box}





.sub-alert{display:flex;align-items:flex-start;gap:12px;padding:16px 18px;margin-top:18px;border:1px solid var(--line);border-radius:18px;background:var(--card)}.sub-alert-icon{display:grid;place-items:center;flex:0 0 38px;width:38px;height:38px;border-radius:13px;background:var(--brand-soft);color:var(--brand-d)}.sub-alert-icon svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.8}.sub-alert-copy{min-width:0;flex:1}.sub-alert-copy strong{display:block;margin:1px 0 4px;font-size:13px;font-weight:850}.sub-alert-copy p{margin:0;color:var(--sub-muted);font-size:12px;line-height:1.9}.sub-alert-actions{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-inline-start:auto}.sub-alert-actions a{display:inline-flex;align-items:center;justify-content:center;padding:9px 13px;border-radius:11px;text-decoration:none;font-size:12px;font-weight:800;background:var(--brand);color:#fff;white-space:nowrap}.sub-alert-actions a.secondary{background:var(--track);color:var(--sub-ink)}
.sub-notice{margin-top:14px;padding:12px 15px;border-radius:14px;background:var(--brand-soft);color:var(--sub-ink);font-size:12px;line-height:1.9}.sub-notice.error{background:rgba(224,69,58,.1);border:1px solid rgba(224,69,58,.2);color:var(--danger)}
.sub-section-head{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin:28px 2px 15px}.sub-section-head h3{margin:0 0 4px;font-size:20px;font-weight:900;letter-spacing:-.3px;color:var(--sub-ink)}.sub-section-head p{margin:0;color:var(--sub-muted);font-size:12px;line-height:1.9}.sub-count{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid var(--line);border-radius:999px;color:var(--sub-muted);font-size:11px;white-space:nowrap}
.sub-plans{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,260px),1fr));gap:16px;align-items:stretch}.sub-plan{position:relative;display:flex;flex-direction:column;min-width:0;overflow:hidden;padding:0;border:1px solid var(--line);border-radius:22px;background:var(--card);box-shadow:0 5px 18px rgba(25,60,55,.045);transition:transform .2s ease,border-color .2s ease,box-shadow .2s ease}.sub-plan:hover{transform:translateY(-3px);border-color:rgba(42,155,143,.48);box-shadow:0 14px 30px rgba(25,90,80,.09)}
.sub-plan-top{padding:20px 20px 17px;border-bottom:1px solid var(--line);background:linear-gradient(145deg,var(--brand-soft),transparent 78%)}.sub-plan-topline{display:flex;align-items:flex-start;justify-content:space-between;gap:10px}.sub-plan-name{margin:0;color:var(--sub-ink);font-size:17px;font-weight:900;line-height:1.7;overflow-wrap:anywhere}.sub-plan-mark{display:grid;place-items:center;flex:0 0 38px;width:38px;height:38px;border:1px solid var(--line);border-radius:13px;background:var(--panel);color:var(--brand-d)}.sub-plan-mark svg{width:20px;height:20px;fill:none;stroke:currentColor;stroke-width:1.7}
.sub-pill{display:inline-flex;align-items:center;gap:5px;margin-top:9px;padding:5px 9px;border-radius:999px;background:var(--brand-soft);color:var(--brand-d);font-size:10px;font-weight:850}.sub-pill.discount{background:rgba(232,163,61,.17);color:#a56a12}:root[data-theme="dark"] .sub-pill.discount{color:#f3c779}
.sub-price{padding:18px 20px 14px}.sub-price-label{display:block;margin-bottom:5px;color:var(--sub-muted);font-size:11px}.sub-price-main{display:flex;align-items:baseline;gap:7px;flex-wrap:wrap}.sub-price-main strong{font-size:clamp(23px,2.4vw,30px);font-weight:950;line-height:1.4;letter-spacing:-.7px;color:var(--sub-ink);overflow-wrap:anywhere}.sub-price-main .currency{font-size:11px;color:var(--sub-muted);font-weight:700}.sub-old-price{display:block;margin-top:5px;color:var(--sub-muted);font-size:11px;line-height:1.8}.sub-old-price del{margin-inline-end:4px}
.sub-plan-facts{display:grid;grid-template-columns:1fr 1fr;gap:9px;padding:0 20px 18px}.sub-fact{padding:11px 10px;border:1px solid var(--line);border-radius:14px;background:var(--panel);min-width:0}.sub-fact-label{display:flex;align-items:center;gap:6px;color:var(--sub-muted);font-size:10px;line-height:1.8}.sub-fact-label svg{width:14px;height:14px;fill:none;stroke:var(--brand-d);stroke-width:1.8;flex:none}.sub-fact strong{display:block;margin-top:3px;color:var(--sub-ink);font-size:16px;font-weight:900;overflow-wrap:anywhere}
.sub-plan-foot{display:flex;flex-direction:column;gap:12px;margin-top:auto;padding:0 20px 20px}.sub-coupon{padding:12px;border:1px dashed var(--line);border-radius:15px;background:var(--panel)}.sub-coupon label{display:flex;align-items:center;gap:7px;margin-bottom:8px;color:var(--sub-ink);font-size:11px;font-weight:800}.sub-coupon label svg{width:15px;height:15px;stroke:var(--brand-d);fill:none;stroke-width:1.8}.sub-coupon-row{display:flex;gap:7px;min-width:0}.sub-coupon input{width:100%;min-width:0;height:42px;padding:0 11px;border:1px solid var(--line);border-radius:11px;background:var(--card);color:var(--sub-ink);font:inherit;font-size:12px;outline:none;transition:border-color .15s,box-shadow .15s}.sub-coupon input:focus{border-color:var(--brand);box-shadow:0 0 0 3px var(--brand-soft)}.sub-coupon input::placeholder{color:var(--sub-muted);opacity:.8}.sub-error{margin-top:7px;color:var(--danger);font-size:11px;line-height:1.8}
.sub-submit{display:flex;align-items:center;justify-content:center;gap:9px;width:100%;min-height:48px;padding:12px 15px;border:1px solid transparent;border-radius:14px;background:linear-gradient(110deg,#238f82,#176b63);box-shadow:0 7px 16px rgba(31,106,99,.18);color:#fff;font:inherit;font-size:12px;font-weight:900;text-align:center;cursor:pointer;transition:transform .18s ease,box-shadow .18s ease,filter .18s ease}.sub-submit:hover{transform:translateY(-1px);box-shadow:0 10px 22px rgba(31,106,99,.25);filter:saturate(1.08)}.sub-submit:focus-visible{outline:3px solid var(--brand);outline-offset:3px}.sub-submit.free{background:linear-gradient(110deg,#278d75,#176b58)}.sub-submit svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:2;flex:none}
.sub-trust{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-top:20px}.sub-trust-item{display:flex;align-items:center;gap:10px;padding:13px;border:1px solid var(--line);border-radius:16px;background:var(--panel);min-width:0}.sub-trust-icon{display:grid;place-items:center;flex:0 0 34px;width:34px;height:34px;border-radius:11px;background:var(--brand-soft);color:var(--brand-d)}.sub-trust-icon svg{width:17px;height:17px;fill:none;stroke:currentColor;stroke-width:1.8}.sub-trust-item b{display:block;color:var(--sub-ink);font-size:11px;font-weight:850;line-height:1.8}.sub-trust-item span{display:block;color:var(--sub-muted);font-size:10px;line-height:1.8}
.sub-empty{padding:34px 18px;text-align:center;border:1px dashed var(--line);border-radius:20px;background:var(--panel)}.sub-empty-icon{display:grid;place-items:center;width:54px;height:54px;margin:0 auto 12px;border-radius:17px;background:var(--brand-soft);color:var(--brand-d)}.sub-empty-icon svg{width:26px;height:26px;fill:none;stroke:currentColor;stroke-width:1.6}.sub-empty b{display:block;margin-bottom:5px;font-size:14px;font-weight:900}.sub-empty p{margin:0;color:var(--sub-muted);font-size:12px;line-height:1.9}
@media(max-width:680px){.sub-alert{flex-wrap:wrap;padding:14px}.sub-alert-actions{width:100%;margin-inline-start:50px}.sub-section-head{align-items:flex-start;flex-direction:column;margin-top:23px}.sub-section-head h3{font-size:18px}.sub-plans{grid-template-columns:1fr;gap:13px}.sub-plan-top{padding:18px 17px 15px}.sub-price{padding:16px 17px 12px}.sub-plan-facts{padding:0 17px 16px}.sub-plan-foot{padding:0 17px 17px}.sub-trust{grid-template-columns:1fr;gap:8px}.sub-trust-item{padding:10px 12px}.sub-trust-item span{font-size:10px}}
@media(prefers-reduced-motion:reduce){.sub-plan,.sub-submit{transition:none}.sub-plan:hover,.sub-submit:hover{transform:none}}
</style>

<div class="sub-page">
  @if(session('error'))<div class="sub-notice error" role="alert">{{ session('error') }}</div>@endif
  @if(session('status'))<div class="sub-notice" role="status">{{ session('status') }}</div>@endif

  @if($subscription)
    <section class="sub-alert" aria-label="وضعیت اشتراک فعلی">
      <div class="sub-alert-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m5 12.5 4.2 4.2L19 7"/><circle cx="12" cy="12" r="9"/></svg></div>
      <div class="sub-alert-copy"><strong>اشتراک فعلی شما فعال است</strong><p>@if($subscription->plan)پلن <b>{{ $subscription->plan->name }}</b> · @endif ظرفیت <b>{{ $F::num($subscription->units_limit) }} واحد</b> · اعتبار تا <b>{{ $F::date($subscription->expires_at, 'Y/m/d') }}</b></p></div>
      <div class="sub-alert-actions"><a href="{{ route('dashboard') }}" class="secondary">بازگشت به داشبورد</a><a href="{{ route('buildings.create') }}">ساخت ساختمان</a></div>
    </section>
  @endif

  <div class="sub-section-head">
    <div><h3>{{ $subscription ? 'تمدید یا تغییر پلن' : 'پلن مناسب خود را انتخاب کنید' }}</h3><p>جزئیات پلن‌ها را مقایسه کنید و فقط در صورت نیاز ادامه دهید.</p></div>
    @if(!$plans->isEmpty())<span class="sub-count">{{ $F::num($plans->count()) }} پلن قابل انتخاب</span>@endif
  </div>

  @if($plans->isEmpty())
    <div class="sub-empty"><div class="sub-empty-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7.5h16v12H4z"/><path d="M8 7.5V5h8v2.5M4 11h16M9 15h2"/></svg></div><b>فعلاً پلنی برای خرید وجود ندارد</b><p>در حال حاضر پلنی برای نمایش وجود ندارد. لطفاً بعداً دوباره سر بزنید.</p></div>
  @else
    <div class="sub-plans">
      @foreach($plans as $plan)
        @php $free = (int) $plan->effectivePrice() === 0; $discounted = $plan->discountIsActive(); @endphp
        <article class="sub-plan">
          <div class="sub-plan-top">
            <div class="sub-plan-topline"><h4 class="sub-plan-name">{{ $plan->name }}</h4><span class="sub-plan-mark" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M3.5 10.5 12 3.8l8.5 6.7"/><path d="M5.5 9.5v10.7h13V9.5M9 20.2v-6.8h6v6.8"/></svg></span></div>
            @if($free && (int) $plan->price === 0)<span class="sub-pill">بدون هزینه</span>@elseif($free)<span class="sub-pill discount">تخفیف ۱۰۰٪</span>@elseif($discounted)<span class="sub-pill discount">تخفیف ویژه</span>@else<span class="sub-pill">پلن اشتراک</span>@endif
          </div>
          <div class="sub-price">
            <span class="sub-price-label">مبلغ قابل پرداخت</span>
            <div class="sub-price-main"><strong>@if($free)رایگان @else{{ $F::money($plan->effectivePrice()) }}@endif</strong>@if(!$free)<span class="currency">تومان</span>@endif</div>
            @if($free && (int) $plan->price > 0)<span class="sub-old-price">قیمت پس از تخفیف کامل؛ <del>{{ $F::money($plan->price) }} تومان</del></span>@elseif($discounted)<span class="sub-old-price">قیمت اصلی: <del>{{ $F::money($plan->price) }} تومان</del></span>@else<span class="sub-old-price">برای {{ $F::num(max(1, (int) $plan->duration_months)) }} ماه اشتراک</span>@endif
          </div>
          <div class="sub-plan-facts">
            <div class="sub-fact"><span class="sub-fact-label"><svg viewBox="0 0 24 24"><path d="M3 20V8l9-5 9 5v12"/><path d="M7 20v-7h10v7M7 9h.01M12 9h.01M17 9h.01"/></svg>ظرفیت ساختمان</span><strong>{{ $F::num($plan->units_limit) }} واحد</strong></div>
            <div class="sub-fact"><span class="sub-fact-label"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg>مدت اشتراک</span><strong>{{ $F::num($plan->duration_months) }} ماه</strong></div>
          </div>
          <div class="sub-plan-foot">
            <form method="POST" action="{{ route('subscription.subscribe', $plan->id) }}" data-confirm="{{ $free ? 'پلن رایگان فعال شود؟' : 'برای پرداخت به درگاه پرداخت هدایت می‌شوید. ادامه می‌دهید؟' }}" data-confirm-title="{{ $free ? 'فعال‌سازی اشتراک' : 'پرداخت اشتراک' }}">
              @csrf
              @if($plan->effectivePrice() > 0)
                <div class="sub-coupon">
                  <label for="coupon-{{ $plan->id }}"><svg viewBox="0 0 24 24"><path d="m20.5 13.5-7 7-10-10v-7h7z"/><circle cx="7.5" cy="7.5" r="1"/></svg>کد تخفیف <span style="font-weight:500;color:var(--sub-muted)">(اختیاری)</span></label>
                  <div class="sub-coupon-row"><input id="coupon-{{ $plan->id }}" name="coupon_code" value="{{ old('coupon_code') }}" placeholder="کد تخفیف را وارد کنید" autocomplete="off" autocapitalize="characters" spellcheck="false" aria-describedby="coupon-help-{{ $plan->id }}"></div>
                  @error('coupon_code')<div class="sub-error" id="coupon-help-{{ $plan->id }}" role="alert">{{ $message }}</div>@enderror
                </div>
              @endif
              <button class="sub-submit {{ $free ? 'free' : '' }}" type="submit">
                @if($free)<svg viewBox="0 0 24 24"><path d="m5 12.5 4.2 4.2L19 7"/></svg>{{ $subscription ? 'انتخاب این پلن' : 'فعال‌سازی رایگان' }}@else<svg viewBox="0 0 24 24"><path d="M4 7h16v13H4z"/><path d="M4 11h16M8 7V4h8v3M8 16h3"/></svg>{{ $subscription ? 'پرداخت و تمدید / تغییر پلن' : 'خرید و فعال‌سازی اشتراک' }}@endif
              </button>
            </form>
          </div>
        </article>
      @endforeach
    </div>
  @endif

  <div class="sub-trust" aria-label="نکات خرید اشتراک">
    <div class="sub-trust-item"><span class="sub-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 3 19 6v5c0 4.5-2.8 7.8-7 10-4.2-2.2-7-5.5-7-10V6z"/><path d="m8.5 12 2.2 2.2 4.8-4.8"/></svg></span><div><b>پرداخت امن</b><span>فعال‌سازی پلن پولی پس از تأیید پرداخت</span></div></div>
    <div class="sub-trust-item"><span class="sub-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg></span><div><b>مدت مشخص</b><span>مدت اشتراک پیش از انتخاب نمایش داده می‌شود</span></div></div>
    <div class="sub-trust-item"><span class="sub-trust-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M4 7.5h16v12H4z"/><path d="M8 7.5V5h8v2.5M8 13h8"/></svg></span><div><b>کد تخفیف اختیاری</b><span>کد معتبر را پیش از پرداخت وارد کنید</span></div></div>
  </div>
</div>
@endsection
