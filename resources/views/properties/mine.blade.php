@extends('layouts.marketplace')
@section('title','آگهی‌های من')
@section('content')
<section class="hero">
    <h1>آگهی‌های من</h1>
    <p>مدیریت آگهی‌ها و اعتبارهای حساب کاربری شما.</p>
    <div class="actions">
        <a class="btn light" href="{{ route('properties.create') }}">ثبت آگهی جدید</a>
        <a class="btn light" href="{{ route('properties.inquiries.index') }}">پیام‌ها و گفتگوها</a>
        <span class="btn light">اعتبار خریداری‌شده: {{ number_format($availableCredits) }} آگهی</span>
    </div>
</section>

<section class="panel" style="margin-bottom:24px">
    <h2>خرید بسته آگهی</h2>
    <p class="muted">سهمیه رایگان برای هر حساب کاربری جداگانه محاسبه می‌شود. اعتبار بسته پس از تأیید قطعی پرداخت به حساب شما افزوده خواهد شد.</p>
    @if(empty($paymentGateways))
        <div class="error">درگاه پرداخت فعالی برای خرید بسته تنظیم نشده است. امکان خرید در حال حاضر وجود ندارد.</div>
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
    @error('quantity')<div class="error">{{ $message }}</div>@enderror
    @if(session('error'))<div class="error">{{ session('error') }}</div>@endif
    @if(session('status'))<div class="notice">{{ session('status') }}</div>@endif
</section>

@if($packageOrders->isNotEmpty())
<section class="panel" style="margin-bottom:24px">
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

<section class="grid">
    @forelse($listings as $listing)
        <article class="card">
            <div class="body">
                <h3>{{ $listing->title }}</h3>
                <div class="muted">{{ $listing->city }} · وضعیت: {{ ['pending'=>'در انتظار تأیید','active'=>'فعال','closed'=>'غیرفعال','rejected'=>'رد شده','draft'=>'پیش‌نویس'][$listing->status] ?? $listing->status }}</div>
                @if($listing->status === 'rejected' && $listing->moderation_note)<p class="muted">توضیح مدیریت: {{ $listing->moderation_note }}</p>@endif
                <div class="actions">
                    <a class="btn light" href="{{ route('properties.show',$listing) }}">مشاهده</a>
                    <a class="btn" href="{{ route('properties.edit',$listing) }}">ویرایش</a>
                    @if($listing->status === 'active')
                        <form method="POST" action="{{ route('properties.close',$listing) }}">@csrf<button class="btn light">غیرفعال‌سازی</button></form>
                    @endif
                    <form method="POST" action="{{ route('properties.destroy',$listing) }}" data-confirm="آیا از حذف این آگهی مطمئن هستید؟">@csrf @method('DELETE')<button class="btn light">حذف</button></form>
                </div>
            </div>
        </article>
    @empty
        <div class="panel">هنوز آگهی ثبت نکرده‌اید.</div>
    @endforelse
</section>
<div class="pagination">{{ $listings->links() }}</div>
@endsection
