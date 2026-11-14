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

@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if(session('error'))<div class="error" role="alert">{{ session('error') }}</div>@endif

@if(($performance['views'] ?? 0) > 0 || ($performance['phone_clicks'] ?? 0) > 0 || ($performance['inquiries'] ?? 0) > 0 || $listings->total() > 0)
<section class="panel performance-panel" aria-labelledby="performance-title">
    <div class="performance-heading">
        <div><h2 id="performance-title">عملکرد آگهی‌های شما</h2><p class="muted">آمار تعامل مخاطبان با آگهی‌های فعال شما</p></div>
        <span class="performance-period">۱۴ روز اخیر</span>
    </div>
    <div class="performance-cards">
        <article><span class="performance-icon">◉</span><small>بازدید آگهی‌ها</small><strong>{{ \App\Support\Fa::num($performance['views']) }}</strong></article>
        <article><span class="performance-icon">☎</span><small>کلیک روی تماس</small><strong>{{ \App\Support\Fa::num($performance['phone_clicks']) }}</strong></article>
        <article><span class="performance-icon">▤</span><small>گفتگوهای شروع‌شده</small><strong>{{ \App\Support\Fa::num($performance['inquiries']) }}</strong></article>
        <article><span class="performance-icon">↗</span><small>نرخ تبدیل بازدید به درخواست</small><strong>{{ \App\Support\Fa::num($performance['conversion_rate'] ?? 0) }}٪</strong></article>
    </div>
    <div class="performance-chart-wrap">
        <h3>روند بازدید روزانه</h3>
        @php
    $maxDailyViews = max(1, (int) $dailyStats->max('views'));
@endphp
        @if($dailyStats->isNotEmpty())
        <div class="performance-chart" role="img" aria-label="نمودار بازدید روزانه در ۱۴ روز اخیر">
            @foreach($dailyStats as $day)
                <div class="performance-day" title="{{ \App\Support\Fa::digits(substr($day->stat_date, 5)) }}: {{ \App\Support\Fa::num($day->views) }} بازدید">
                    <span class="performance-bar" style="height: {{ max(4, (int) round(($day->views / $maxDailyViews) * 100)) }}%"></span>
                    <small>{{ \App\Support\Fa::digits(substr($day->stat_date, 8, 2)) }}</small>
                </div>
            @endforeach
        </div>
        <p class="muted performance-chart-note">هر ستون تعداد بازدیدهای ثبت‌شده در آن روز را نشان می‌دهد.</p>
        @else
        <p class="muted">هنوز داده‌ای برای نمایش نمودار ثبت نشده است. با بازدید مخاطبان، آمار این بخش به‌روز می‌شود.</p>
        @endif
    </div>
</section>
<style>
.performance-panel{margin:0 0 24px}.performance-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.performance-heading h2{margin:0 0 5px}.performance-heading p{margin:0}.performance-period{white-space:nowrap;border-radius:30px;padding:6px 11px;background:#eef6f1;color:#39745c;font-size:11px}.performance-cards{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:20px}.performance-cards article{display:grid;gap:7px;padding:16px;border:1px solid #e7eee9;border-radius:16px;background:#fbfdfb}.performance-icon{display:grid;place-items:center;width:34px;height:34px;border-radius:11px;background:#eaf5ee;color:#247456;font-size:19px}.performance-cards small{color:#71847a;font-size:12px}.performance-cards strong{font-size:23px;color:#173f32;font-variant-numeric:tabular-nums}.performance-chart-wrap{margin-top:22px;padding-top:18px;border-top:1px solid #edf1ee}.performance-chart-wrap h3{font-size:14px;margin:0 0 14px}.performance-chart{height:130px;display:flex;align-items:stretch;gap:8px;padding:0 3px;border-bottom:1px solid #e5ece7}.performance-day{display:flex;flex:1;min-width:0;flex-direction:column;align-items:center;justify-content:flex-end;gap:6px}.performance-bar{display:block;width:min(100%,24px);min-height:4px;border-radius:6px 6px 2px 2px;background:linear-gradient(180deg,#3c9b70,#17664d)}.performance-day small{font-size:10px;color:#809087}.performance-chart-note{font-size:11px;margin:10px 0 0}@media(max-width:520px){.performance-cards{grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}.performance-cards article{padding:11px 9px}.performance-cards strong{font-size:19px}.performance-cards small{font-size:10px}.performance-heading h2{font-size:17px}}
</style>
@endif

<details class="panel collapsible" id="packages" @if($errors->has('quantity') || session('error')) open @endif>
    <summary>
        <span class="sum-text"><b>خرید بسته آگهی</b><small>اعتبار خریداری‌شده: {{ \App\Support\Fa::num($availableCredits) }} آگهی</small></span>
        <span class="sum-chev" aria-hidden="true"></span>
    </summary>
    <div class="collapsible-body">
        <p class="muted">سهمیه رایگان برای هر حساب کاربری جداگانه محاسبه می‌شود. اعتبار بسته پس از تأیید قطعی پرداخت به حساب شما افزوده خواهد شد.</p>
        @if(empty($paymentGateways))
            <div class="info-bar" role="status"><svg class="icon" aria-hidden="true"><use href="#i-clock"/></svg><span>خرید بسته فعلاً در دسترس نیست؛ درگاه پرداخت هنوز فعال نشده است.</span></div>
        @elseif($packages->isEmpty())
            <div class="info-bar" role="status"><svg class="icon" aria-hidden="true"><use href="#i-clock"/></svg><span>در حال حاضر بسته فعالی برای خرید تعریف نشده است.</span></div>
        @else
            <div class="grid">
                @foreach($packages as $package)
                    <article class="card">
                        <div class="body">
                            <h3>{{ \App\Support\Fa::num((int) $package['quantity']) }} آگهی</h3>
                            <p class="muted">اعتبار قابل استفاده برای انتشار آگهی‌های جدید</p>
                            <strong>{{ \App\Support\Fa::money((int) $package['price']) }}</strong>
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
        @error('quantity')<div class="error" role="alert">{{ $message }}</div>@enderror
    </div>
</details>

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

<h2 class="section-title">آگهی‌های ثبت‌شده</h2>
<section class="grid my-grid" aria-label="آگهی‌های من">
    @forelse($listings as $listing)
        @php
            $photo = $listing->photoUrls()[0] ?? null;
            $isExpired = $listing->status === 'expired' || ($listing->status === 'active' && $listing->expires_at && $listing->expires_at->isPast());
            $statusLabel = $isExpired ? 'منقضی‌شده' : (['pending'=>'در انتظار تأیید','active'=>'فعال','closed'=>'غیرفعال','rejected'=>'رد شده','draft'=>'پیش‌نویس'][$listing->status] ?? $listing->status);
            $statusClass = $isExpired ? 'closed' : $listing->status;
        @endphp
        <article class="card my-card">
            <a class="photo @if($photo) has-img @endif" href="{{ route('properties.show',$listing) }}" aria-label="مشاهده آگهی {{ $listing->title }}">
                <span class="badge st-{{ $statusClass }}">{{ $statusLabel }}</span>
                @if($photo)<img src="{{ $photo }}" alt="" loading="lazy" decoding="async">@else<svg class="icon" aria-hidden="true"><use href="#i-image"/></svg>@endif
            </a>
            <div class="body">
                <h3>{{ $listing->title }}</h3>
                <div class="loc"><svg class="icon" aria-hidden="true"><use href="#i-pin"/></svg>{{ $listing->city }}@if($listing->neighborhood)، {{ $listing->neighborhood }}@endif</div>
                <div class="price">
                    @foreach($listing->priceRows(true) as $row)
                        <div>@if($row['label'])<small>{{ $row['label'] }}</small>@endif<span>{{ $row['value'] }}</span></div>
                    @endforeach
                </div>
                <div class="my-meta"><span>وضعیت: {{ $statusLabel }}</span><span class="views"><svg class="icon" aria-hidden="true"><use href="#i-clock"/></svg>{{ \App\Support\Fa::num($listing->views_count ?? 0) }} بازدید</span><span>{{ \App\Support\Fa::num($listing->phone_clicks_count ?? 0) }} کلیک تماس</span><span>{{ \App\Support\Fa::num($listing->inquiries_count ?? 0) }} درخواست</span><span>نرخ تبدیل: {{ \App\Support\Fa::num(($listing->views_count ?? 0) > 0 ? round(($listing->inquiries_count / $listing->views_count) * 100, 1) : 0) }}٪</span></div>
                <a class="btn light" href="{{ route('properties.inquiries.index', ['listing_id' => $listing->id]) }}">مدیریت درخواست‌های این آگهی</a>
                @if($listing->expires_at)<div class="muted">{{ $isExpired ? 'منقضی‌شده در' : 'اعتبار تا' }} {{ \App\Support\Fa::date($listing->expires_at, 'Y/m/d H:i') }}</div>@endif
                @if($listing->status === 'rejected' && $listing->moderation_note)<p class="muted">توضیح مدیریت: {{ $listing->moderation_note }}</p>@endif
                <div class="card-actions">
                    <a class="btn" href="{{ route('properties.edit',$listing) }}">ویرایش</a>
                    <details class="more-menu">
                        <summary class="btn light" aria-label="گزینه‌های بیشتر برای {{ $listing->title }}" aria-haspopup="menu">⋯</summary>
                        <div class="menu" role="menu">
                            <a role="menuitem" href="{{ route('properties.show',$listing) }}">مشاهده آگهی</a>
                            @if(in_array($listing->status, ['active', 'expired'], true))
                                <form method="POST" action="{{ route('properties.renew',$listing) }}" data-confirm="با تمدید، یک اعتبار از حساب شما کسر می‌شود. ادامه می‌دهید؟">@csrf<button role="menuitem" type="submit">تمدید آگهی</button></form>
                            @endif
                            @if($listing->status === 'active' && ! $isExpired)
                                <form method="POST" action="{{ route('properties.close',$listing) }}">@csrf<button role="menuitem" type="submit">غیرفعال‌سازی</button></form>
                            @endif
                            <button role="menuitem" type="button" class="danger" data-delete-open data-action="{{ route('properties.destroy',$listing) }}" data-title="{{ $listing->title }}">حذف آگهی</button>
                        </div>
                    </details>
                </div>
            </div>
        </article>
    @empty
        <div class="empty" style="grid-column:1/-1">
            <div class="empty-ico"><svg class="icon" aria-hidden="true"><use href="#i-list"/></svg></div>
            <h3>هنوز آگهی ثبت نکرده‌اید</h3>
            <p class="muted">اولین آگهی‌تان را در چند دقیقه ثبت کنید؛ بعد از تأیید مدیریت در بازار املاک نمایش داده می‌شود و پیام خریداران همین‌جا می‌رسد.</p>
            <div class="actions" style="justify-content:center"><a class="btn lg" href="{{ route('properties.create') }}"><svg class="icon" aria-hidden="true"><use href="#i-plus"/></svg>ثبت اولین آگهی</a><a class="btn light lg" href="{{ route('properties.index') }}">مشاهده آگهی‌های بازار</a></div>
        </div>
    @endforelse
</section>

<dialog class="confirm-dialog" id="delete-dialog" aria-labelledby="delete-title">
    <form method="POST" id="delete-form" action="">
        {{-- تأیید حذف با مودال خودِ صفحه (بدون وابستگی به ui.js که در لایه‌ی بازار بارگذاری نمی‌شود) --}}
        @csrf
        <input type="hidden" name="_method" value="DELETE">
        <h2 id="delete-title">حذف آگهی؟</h2>
        <p>آگهی «<b id="delete-name"></b>» و تصاویر آن برای همیشه حذف می‌شود و قابل بازگشت نیست.</p>
        <div class="actions">
            <button class="btn danger" type="submit">بله، حذف شود</button>
            <button class="btn light" type="button" data-delete-cancel>انصراف</button>
        </div>
    </form>
</dialog>
<div class="pagination">{{ $listings->links() }}</div>
@push('scripts')
<script>
(function () {
    var dlg = document.getElementById('delete-dialog');
    var form = document.getElementById('delete-form');
    var name = document.getElementById('delete-name');
    document.addEventListener('click', function (e) {
        var open = e.target.closest('[data-delete-open]');
        if (open) {
            form.action = open.getAttribute('data-action');
            name.textContent = open.getAttribute('data-title');
            var menu = open.closest('details'); if (menu) menu.open = false;
            if (dlg.showModal) { dlg.showModal(); } else { dlg.setAttribute('open', ''); }
            return;
        }
        if (e.target.closest('[data-delete-cancel]')) { if (dlg.close) { dlg.close(); } else { dlg.removeAttribute('open'); } return; }
        if (e.target === dlg && dlg.close) { dlg.close(); return; }
        // بستن منوی «⋯» با کلیک بیرون از آن
        document.querySelectorAll('.more-menu[open]').forEach(function (d) { if (!d.contains(e.target)) d.open = false; });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.more-menu[open]').forEach(function (d) { d.open = false; var s = d.querySelector('summary'); if (s) s.focus(); });
    });
})();
</script>
@endpush
@endsection
