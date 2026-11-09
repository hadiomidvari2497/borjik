@extends('admin.layout')
@section('title', 'مدیریت بازارچه آگهی‌های ملکی')
@section('content')
@php
    $F = \App\Support\Fa::class;
    $statusLabels = ['pending' => 'در انتظار تأیید', 'active' => 'فعال', 'expired' => 'منقضی‌شده', 'closed' => 'غیرفعال', 'rejected' => 'ردشده'];
    $typeLabels = ['sale' => 'فروش', 'rent' => 'اجاره', 'buy_request' => 'درخواست خرید', 'rent_request' => 'درخواست اجاره'];
@endphp

<div class="card">
    <div class="page-h">
        <div>
            <h2>مدیریت بازارچه املاک</h2>
            <p style="color:var(--muted);font-size:13px;line-height:1.8;margin:7px 0 0">رسیدگی به آگهی‌ها، کنترل وضعیت انتشار و پایش خرید بسته‌ها و درآمد بازارچه.</p>
        </div>
        <a class="btn sec sm" href="{{ route('admin.property-listings.settings') }}">تنظیمات سهمیه و بسته‌ها</a>
        <a class="btn sec sm" href="{{ route('admin.property-listings.reports') }}">گزارش‌های تخلف @if($pendingReports > 0)<span class="pill">{{ $F::num($pendingReports) }}</span>@endif</a>
    </div>
</div>

<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px">
    <div class="card"><div class="muted">کل آگهی‌ها</div><div style="font-size:25px;font-weight:800;margin-top:8px">{{ $F::num($listingStats->total ?? 0) }}</div></div>
    <div class="card"><div class="muted">در انتظار تأیید</div><div style="font-size:25px;font-weight:800;margin-top:8px">{{ $F::num($listingStats->pending ?? 0) }}</div></div>
    <div class="card"><div class="muted">آگهی‌های فعال</div><div style="font-size:25px;font-weight:800;margin-top:8px">{{ $F::num($listingStats->active ?? 0) }}</div></div>
    <div class="card"><div class="muted">منقضی‌شده</div><div style="font-size:25px;font-weight:800;margin-top:8px">{{ $F::num($listingStats->expired ?? 0) }}</div></div>
    <div class="card"><div class="muted">غیرفعال / ردشده</div><div style="font-size:25px;font-weight:800;margin-top:8px">{{ $F::num(($listingStats->closed ?? 0) + ($listingStats->rejected ?? 0)) }}</div></div>
    <div class="card"><div class="muted">درآمد بسته‌های آگهی</div><div style="font-size:21px;font-weight:800;margin-top:8px">{{ $F::money($orderStats->revenue ?? 0) }}</div><small class="muted">فقط پرداخت‌های تأییدشده</small></div>
</div>

<div class="grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px">
    <div class="card"><div class="muted">کل سفارش‌های بسته</div><div style="font-size:22px;font-weight:800;margin-top:8px">{{ $F::num($orderStats->total ?? 0) }}</div></div>
    <div class="card"><div class="muted">پرداخت موفق</div><div style="font-size:22px;font-weight:800;margin-top:8px">{{ $F::num($orderStats->paid_count ?? 0) }}</div></div>
    <div class="card"><div class="muted">در انتظار / ناموفق</div><div style="font-size:22px;font-weight:800;margin-top:8px">{{ $F::num(($orderStats->pending_count ?? 0) + ($orderStats->failed_count ?? 0)) }}</div></div>
    <div class="card"><div class="muted">اعتبار خریداری‌شده</div><div style="font-size:22px;font-weight:800;margin-top:8px">{{ $F::num($creditStats->purchased ?? 0) }}</div></div>
    <div class="card"><div class="muted">اعتبار مصرف‌شده</div><div style="font-size:22px;font-weight:800;margin-top:8px">{{ $F::num($creditStats->consumed ?? 0) }}</div></div>
    <div class="card"><div class="muted">اعتبار باقی‌مانده کاربران</div><div style="font-size:22px;font-weight:800;margin-top:8px">{{ $F::num($creditStats->available ?? 0) }}</div></div>
</div>

<div class="card">
    <div class="page-h"><h2>آگهی‌های ثبت‌شده</h2><span class="pill">{{ $F::num($listings->total()) }} مورد</span></div>
    <form method="GET" action="{{ route('admin.property-listings.index') }}" class="fgrid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));align-items:end">
        <div class="fld"><label for="q">جست‌وجو</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="عنوان، شهر، نام یا موبایل کاربر"></div>
        <div class="fld"><label for="status">وضعیت</label><select id="status" name="status"><option value="">همه وضعیت‌ها</option>@foreach($statusLabels as $key => $label)<option value="{{ $key }}" @selected(($filters['status'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="fld"><label for="listing_type">نوع آگهی</label><select id="listing_type" name="listing_type"><option value="">همه نوع‌ها</option>@foreach($typeLabels as $key => $label)<option value="{{ $key }}" @selected(($filters['listing_type'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></div>
        <div class="fld" style="display:flex;gap:8px;align-items:end"><button class="btn" type="submit">اعمال فیلتر</button><a class="btn sec" href="{{ route('admin.property-listings.index') }}">پاک‌کردن</a></div>
    </form>
    @if($listings->isEmpty())
        <div class="empty">آگهی‌ای با این مشخصات پیدا نشد.</div>
    @else
        <div class="tbl-wrap"><table class="tbl">
            <thead><tr><th>آگهی</th><th>ثبت‌کننده</th><th>نوع</th><th>شهر</th><th>قیمت / ودیعه</th><th>وضعیت</th><th>تاریخ ثبت</th><th></th></tr></thead>
            <tbody>
            @foreach($listings as $listing)
                <tr>
                    <td><b>{{ $listing->title }}</b><div class="muted" style="font-size:11px">#{{ $F::num($listing->id) }}</div></td>
                    <td>{{ $listing->user?->display_name ?? 'کاربر حذف‌شده' }}<div class="muted" dir="ltr" style="font-size:11px;text-align:right">{{ $listing->user?->mobile ?? '—' }}</div></td>
                    <td>{{ $typeLabels[$listing->listing_type] ?? $listing->listing_type }}</td>
                    <td>{{ $listing->city }}</td>
                    <td style="white-space:nowrap">{{ $F::money($listing->price ?? $listing->deposit ?? $listing->monthly_rent ?? 0) }}</td>
                    <td><span class="pill {{ $listing->status === 'active' ? '' : 'h' }}">{{ $statusLabels[$listing->status] ?? $listing->status }}</span></td>
                    <td style="white-space:nowrap">{{ $listing->created_at ? $F::date($listing->created_at, 'j F Y') : '—' }}</td>
                    <td><a class="btn sec sm" href="{{ route('admin.property-listings.show', $listing->id) }}">بررسی آگهی</a></td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
        <div style="margin-top:14px">{{ $listings->links() }}</div>
    @endif
</div>

<div class="card">
    <div class="page-h"><h2>آخرین خرید بسته‌ها</h2><span class="pill">{{ $F::num($recentOrders->count()) }} سفارش اخیر</span></div>
    @if($recentOrders->isEmpty())
        <div class="empty">هنوز سفارشی برای خرید بسته ثبت نشده است.</div>
    @else
        <div class="tbl-wrap"><table class="tbl">
            <thead><tr><th>سفارش</th><th>کاربر</th><th>تعداد اعتبار</th><th>مبلغ</th><th>درگاه</th><th>وضعیت</th><th>تاریخ</th></tr></thead>
            <tbody>
            @foreach($recentOrders as $order)
                <tr>
                    <td>#{{ $F::num($order->id) }}</td>
                    <td>{{ $order->user?->display_name ?? 'کاربر حذف‌شده' }}<div class="muted" dir="ltr" style="text-align:right;font-size:11px">{{ $order->user?->mobile ?? '—' }}</div></td>
                    <td>{{ $F::num($order->quantity) }}</td>
                    <td>{{ $F::money($order->amount) }}</td>
                    <td>{{ $order->raw_response['gateway_name'] ?? $order->gateway }}</td>
                    <td>{{ ['pending'=>'در انتظار پرداخت','paid'=>'پرداخت موفق','failed'=>'ناموفق'][$order->status] ?? $order->status }}</td>
                    <td>{{ $order->created_at ? $F::date($order->created_at, 'j F Y H:i') : '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table></div>
    @endif
</div>
@endsection
