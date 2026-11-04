@extends('admin.layout')
@section('title', 'گزارش‌های تخلف بازارچه')
@section('content')
@php
    $F = \App\Support\Fa::class;
    $statusLabels = ['pending' => 'در انتظار بررسی', 'actioned' => 'اقدام انجام شد', 'dismissed' => 'مختومه بدون اقدام'];
    $reasonLabels = ['fraud' => 'احتمال کلاهبرداری', 'incorrect' => 'اطلاعات نادرست', 'duplicate' => 'آگهی تکراری', 'inappropriate' => 'محتوای نامناسب', 'prohibited' => 'مورد غیرمجاز', 'other' => 'سایر موارد'];
@endphp
<div class="card">
    <div class="page-h">
        <div><h2>گزارش‌های تخلف بازارچه</h2><p class="muted">گزارش‌های ثبت‌شده توسط کاربران را بررسی و نتیجه را به گزارش‌دهنده اعلام کنید.</p></div>
        <a class="btn sec sm" href="{{ route('admin.property-listings.index') }}">بازگشت به بازارچه</a>
    </div>
    <div class="actions">
        @foreach($statusLabels as $key => $label)
            <a class="btn {{ $status === $key ? '' : 'sec' }} sm" href="{{ route('admin.property-listings.reports', ['status' => $key]) }}">{{ $label }} <small>({{ $F::num($counts[$key] ?? 0) }})</small></a>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="page-h"><h2>{{ $statusLabels[$status] ?? $status }}</h2><span class="pill">{{ $F::num($reports->total()) }} گزارش</span></div>
    @forelse($reports as $report)
        <article style="border:1px solid var(--line);border-radius:14px;padding:16px;margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap">
                <div>
                    <b>گزارش #{{ $F::num($report->id) }} · {{ $reasonLabels[$report->reason] ?? $report->reason }}</b>
                    <p class="muted" style="margin:6px 0">آگهی: {{ $report->listing?->title ?? 'آگهی حذف‌شده' }} · گزارش‌دهنده: {{ $report->reporter?->display_name ?? 'کاربر حذف‌شده' }} ({{ $report->reporter?->mobile ?? '—' }})</p>
                </div>
                <a class="btn sec sm" href="{{ route('admin.property-listings.show', $report->property_listing_id) }}">بررسی آگهی</a>
            </div>
            @if($report->details)<p style="white-space:pre-line;line-height:1.8">{{ $report->details }}</p>@endif
            <small class="muted">تاریخ ثبت: {{ $report->created_at ? $F::date($report->created_at, 'j F Y H:i') : '—' }}</small>
            @if($report->status === 'pending')
                <form method="POST" action="{{ route('admin.property-listings.reports.update', $report->id) }}" class="fgrid" style="margin-top:14px">
                    @csrf
                    @method('PATCH')
                    <div class="fld">
                        <label>نتیجه بررسی</label>
                        <select name="status" required>
                            <option value="actioned">تخلف تأیید شد و اقدام انجام شد</option>
                            <option value="dismissed">تخلف تأیید نشد / مختومه</option>
                        </select>
                    </div>
                    <div class="fld">
                        <label>توضیح نتیجه برای گزارش‌دهنده</label>
                        <textarea name="resolution_note" rows="2" maxlength="2000" required placeholder="نتیجه بررسی را بنویسید"></textarea>
                    </div>
                    <button class="btn" type="submit">ثبت نتیجه و ارسال اعلان</button>
                </form>
            @else
                <p class="muted" style="margin-top:12px">نتیجه: {{ $statusLabels[$report->status] ?? $report->status }} · {{ $report->resolution_note }}</p>
            @endif
        </article>
    @empty
        <div class="empty">گزارشی با این وضعیت وجود ندارد.</div>
    @endforelse
    @if($reports->hasPages())<div style="margin-top:14px">{{ $reports->links() }}</div>@endif
</div>
@endsection
