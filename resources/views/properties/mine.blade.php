@extends('layouts.marketplace')
@section('title','آگهی‌های من')
@push('head')<link rel="stylesheet" href="{{ asset('css/listings-mine.css') }}?v={{ @filemtime(public_path('css/listings-mine.css')) }}">@endpush
@section('content')
<section class="hero">
    <h1>آگهی‌های من</h1>
    <p>مدیریت آگهی‌ها و اعتبارهای حساب کاربری شما.</p>
    <div class="actions">
        <a class="btn light" href="{{ route('properties.create') }}">ثبت آگهی جدید</a>
        <a class="btn light" href="{{ route('properties.inquiries.index') }}">پیام‌ها و گفتگوها</a>
        <a class="btn light" href="#packages">اعتبار: {{ number_format($availableCredits) }} آگهی</a>
    </div>
</section>

@if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
@if(session('error'))<div class="lform-errors" role="alert">{{ session('error') }}</div>@endif
@error('quantity')<div class="lform-errors" role="alert">{{ $message }}</div>@enderror

<section class="lcards" aria-label="آگهی‌های من">
    @forelse($listings as $listing)
        @php
            $st = ['pending'=>['در انتظار تأیید','warn'],'active'=>['فعال','ok'],'closed'=>['غیرفعال','off'],'rejected'=>['رد شده','bad'],'draft'=>['پیش‌نویس','off']][$listing->status] ?? [$listing->status,'off'];
            $thumb = $listing->photoUrls()[0] ?? null;
            $price = ($listing->priceRows(true)[0] ?? null);
        @endphp
        <article class="lcard">
            <a class="lc-img" href="{{ route('properties.show',$listing) }}" tabindex="-1" aria-hidden="true">@if($thumb)<img src="{{ $thumb }}" alt="" loading="lazy" decoding="async">@else<svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>@endif</a>
            <div class="lc-body">
                <div class="lc-top"><span class="st st-{{ $st[1] }}">{{ $st[0] }}</span><span class="tag plain sm">{{ $listing->listingTypeLabel() }}</span></div>
                <a class="lc-title" href="{{ route('properties.show',$listing) }}">{{ $listing->title }}</a>
                <div class="lc-meta"><span>{{ $listing->city }}@if($listing->neighborhood)، {{ $listing->neighborhood }}@endif</span>@if($listing->area)<span>{{ \App\Support\Fa::num($listing->area) }} متر</span>@endif</div>
                @if($price)<div class="lc-price">@if($price['label'])<small>{{ $price['label'] }}</small> @endif{{ $price['value'] }}</div>@endif
                <div class="lc-stats">
                    <a href="{{ route('properties.inquiries.index') }}"><svg class="icon" aria-hidden="true"><use href="#i-chat"/></svg>{{ $listing->inquiries_count ? \App\Support\Fa::num($listing->inquiries_count).' گفتگو' : 'بدون گفتگو' }}</a>
                    @if($listing->created_at)<span><svg class="icon" aria-hidden="true"><use href="#i-clock"/></svg>{{ \App\Support\Fa::date($listing->created_at, 'j F Y') }}</span>@endif
                </div>
                @if($listing->status === 'rejected' && $listing->moderation_note)<p class="lc-note">توضیح مدیریت: {{ $listing->moderation_note }}</p>@endif
            </div>
            <div class="lc-actions">
                <a class="btn" href="{{ route('properties.edit',$listing) }}">ویرایش</a>
                <a class="btn light" href="{{ route('properties.show',$listing) }}">مشاهده</a>
                <details class="kebab">
                    <summary aria-label="گزینه‌های بیشتر"><svg class="icon" aria-hidden="true"><use href="#i-more"/></svg></summary>
                    <div class="kmenu">
                        @if($listing->status === 'active')
                            <form method="POST" action="{{ route('properties.close',$listing) }}" data-confirm="این آگهی غیرفعال شود؟ دیگر در فهرست عمومی دیده نمی‌شود." data-confirm-ok="غیرفعال‌سازی">@csrf<button type="submit">غیرفعال‌سازی آگهی</button></form>
                        @endif
                        <form method="POST" action="{{ route('properties.destroy',$listing) }}" data-confirm="آیا از حذف این آگهی مطمئن هستید؟ این کار قابل بازگشت نیست." data-confirm-ok="حذف آگهی" data-danger>@csrf @method('DELETE')<button type="submit" class="danger">حذف آگهی</button></form>
                    </div>
                </details>
            </div>
        </article>
    @empty
        <div class="lempty"><div class="ico"><svg class="icon" aria-hidden="true"><use href="#i-doc"/></svg></div><h2>هنوز آگهی ثبت نکرده‌اید</h2><p>اولین آگهی‌تان را در چند دقیقه ثبت کنید تا خریداران و مستأجران آن را ببینند.</p><a class="btn lg" href="{{ route('properties.create') }}"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>ثبت اولین آگهی</a></div>
    @endforelse
</section>

<details class="panel pkgs" id="packages" @if($errors->has('quantity') || session('error')) open @endif>
    <summary><span><b>خرید بسته آگهی</b><small>اعتبار شما: {{ number_format($availableCredits) }} آگهی</small></span><svg class="icon" aria-hidden="true"><use href="#i-chevron"/></svg></summary>
    <p class="muted">سهمیه رایگان برای هر حساب کاربری جداگانه محاسبه می‌شود. اعتبار بسته پس از تأیید قطعی پرداخت به حساب شما افزوده خواهد شد.</p>
    @if(empty($paymentGateways))
        <p class="note">خرید بسته در حال حاضر در دسترس نیست (درگاه پرداخت فعالی تنظیم نشده است).</p>
    @elseif($packages->isEmpty())
        <p class="muted">در حال حاضر بسته فعالی برای خرید تعریف نشده است.</p>
    @else
        <div class="grid">
            @foreach($packages as $package)
                <article class="card">
                    <div class="body">
                        <h3>{{ number_format((int) $package['quantity']) }} آگهی</h3>
                        <p class="muted">اعتبار قابل استفاده برای انتشار آگهی‌های جدید</p>
                        <strong>{{ number_format((int) $package['price']) }} تومان</strong>
                        <form method="POST" action="{{ route('properties.packages.buy') }}" style="margin-top:14px">
                            @csrf
                            <input type="hidden" name="quantity" value="{{ (int) $package['quantity'] }}">
                            @if(count($paymentGateways) > 1)
                                <label class="field">درگاه پرداخت
                                    <select name="gateway_id" required>
                                        @foreach($paymentGateways as $gatewayId => $gateway)
                                            <option value="{{ $gatewayId }}">{{ $gateway['provider_name'] ?? $gateway['driver'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @else
                                <input type="hidden" name="gateway_id" value="{{ array_key_first($paymentGateways) }}">
                            @endif
                            <button class="btn" type="submit">خرید و پرداخت</button>
                        </form>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</details>

@if($packageOrders->isNotEmpty())
<section class="panel" style="margin:18px 0 24px">
    <h2>تاریخچه خرید بسته‌ها</h2>
    <div style="overflow-x:auto">
        <table>
            <thead><tr><th>تاریخ</th><th>تعداد</th><th>مبلغ</th><th>درگاه</th><th>وضعیت</th><th>کد پیگیری</th></tr></thead>
            <tbody>
            @foreach($packageOrders as $order)
                <tr>
                    <td>{{ $order->created_at ? \App\Support\Fa::date($order->created_at, 'Y/m/d H:i') : '—' }}</td>
                    <td>{{ number_format($order->quantity) }}</td>
                    <td>{{ \App\Support\Fa::money($order->amount) }}</td>
                    <td>{{ $order->raw_response['gateway_name'] ?? $order->gateway }}</td>
                    <td>{{ ['pending'=>'در انتظار پرداخت','paid'=>'پرداخت موفق','failed'=>'ناموفق'][$order->status] ?? $order->status }}</td>
                    <td>{{ $order->ref_id ?: '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>
@endif

<div class="pagination">{{ $listings->links() }}</div>
@endsection
