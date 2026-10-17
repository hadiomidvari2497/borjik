@php
    $F = \App\Support\Fa::class;
    $ended = $poll->status !== 'open' || now()->gte($poll->ends_at);
    $started = now()->gte($poll->starts_at);
    $total = (int) $poll->votes_count;
@endphp
@extends('layouts.app')
@section('title', 'جزئیات نظرسنجی')
@section('content')
<style>
.poll-detail{--d-ink:#172b3a;--d-muted:#718096;--d-line:#e3eaf0;color:var(--d-ink)}
.poll-detail-hero{position:relative;isolation:isolate;overflow:hidden;border-radius:23px;padding:clamp(22px,4vw,34px);margin-bottom:18px;background:radial-gradient(circle at 9% 10%,#a5e6d833,transparent 28%),linear-gradient(125deg,#123d42,#176b63 58%,#398d83);color:#fff;box-shadow:0 17px 38px #175c5525}
.poll-detail-hero:after{content:"";position:absolute;z-index:-1;width:240px;height:240px;border:1px solid #ffffff28;border-radius:50%;inset-inline-end:-80px;top:-120px;box-shadow:0 0 0 28px #ffffff08,0 0 0 58px #ffffff07}
.poll-detail-topline{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:17px}
.poll-back{display:inline-flex;align-items:center;gap:7px;color:#e2f7ef;text-decoration:none;font-size:12px;font-weight:700}
.poll-detail-status{display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border:1px solid #ffffff3b;border-radius:999px;background:#ffffff16;color:#f3fffa;font-size:11px;font-weight:750}
.poll-detail-hero h2{font-size:clamp(22px,3vw,31px);font-weight:850;line-height:1.7;color:#fff;margin:0 0 8px;overflow-wrap:anywhere}
.poll-detail-hero p{font-size:13px;line-height:2;color:#d9f0e9;white-space:pre-line;max-width:780px;margin:0}
.poll-detail-hero-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:19px}
.poll-detail-hero-meta span{display:inline-flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid #ffffff25;border-radius:10px;background:#ffffff10;color:#f0fff8;font-size:11px}
.poll-detail-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:18px}
.poll-detail-stat{padding:17px;border:1px solid var(--d-line);border-radius:16px;background:var(--card,#fff);box-shadow:0 6px 20px #1b3b4a06;min-width:0}
.poll-detail-stat-top{display:flex;align-items:center;justify-content:space-between;gap:8px;color:var(--d-muted);font-size:11px}
.poll-detail-stat-icon{display:grid;place-items:center;width:32px;height:32px;border-radius:11px;background:#eaf6f1;color:#287d6e;font-size:16px}
.poll-detail-stat strong{display:block;font-size:23px;font-weight:850;line-height:1.4;color:var(--d-ink);margin-top:9px;overflow-wrap:anywhere}
.poll-detail-stat small{display:block;font-size:10px;color:var(--d-muted);margin-top:3px}
.poll-detail-panel{background:var(--card,#fff);border:1px solid var(--d-line);border-radius:19px;overflow:hidden;margin-bottom:18px;box-shadow:0 7px 24px #1b3b4a07}
.poll-detail-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:19px 22px;border-bottom:1px solid var(--d-line);background:linear-gradient(180deg,#fff,#fbfdfd)}
.poll-detail-panel-title{display:flex;align-items:center;gap:11px}.poll-detail-panel-icon{display:grid;place-items:center;width:39px;height:39px;border-radius:13px;background:#eaf6f1;color:#287d6e;font-size:18px}
.poll-detail-panel-head h3{font-size:15px;font-weight:850;margin:0 0 4px;color:var(--d-ink)}.poll-detail-panel-head p{font-size:11px;color:var(--d-muted);margin:0;line-height:1.8}
.poll-detail-panel-body{padding:21px 22px}
.poll-detail-results{display:grid;gap:17px}
.poll-detail-result-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:8px}
.poll-detail-result-name{font-size:12px;font-weight:800;color:#344b5a;line-height:1.8;overflow-wrap:anywhere}
.poll-detail-result-count{white-space:nowrap;font-size:11px;font-weight:800;color:#176b63}
.poll-detail-track{height:10px;background:#edf2f3;border-radius:99px;overflow:hidden}.poll-detail-fill{height:100%;border-radius:99px;background:linear-gradient(90deg,#176b63,#62bca3)}
.poll-detail-result-foot{display:flex;justify-content:space-between;gap:8px;font-size:10px;color:var(--d-muted);margin-top:6px}
.poll-detail-empty{padding:32px 18px;text-align:center;border:1px dashed #d3e0dc;border-radius:15px;background:#fbfdfc;color:var(--d-muted)}
.poll-detail-empty strong{display:block;font-size:13px;color:#304858;margin:8px 0 5px}.poll-detail-empty p{font-size:11px;line-height:1.9;margin:0}
.poll-detail-privacy{display:flex;align-items:flex-start;gap:11px;padding:14px;border:1px solid #dcece5;border-radius:13px;background:#f3faf7;color:#40665c;font-size:12px;line-height:1.9}
.poll-detail-privacy-icon{display:grid;place-items:center;flex:0 0 auto;width:31px;height:31px;border-radius:10px;background:#dff3e9;color:#21795f}
.poll-detail-table-wrap{overflow-x:auto}.poll-detail-table{width:100%;border-collapse:collapse;min-width:560px}
.poll-detail-table th,.poll-detail-table td{padding:13px 15px;text-align:right;border-bottom:1px solid #edf1f4;vertical-align:top;font-size:12px}
.poll-detail-table th{font-size:10px;font-weight:800;color:#718096;background:#f8fafb;white-space:nowrap}
.poll-detail-table tbody tr:hover{background:#fbfdfc}.poll-detail-participant{font-weight:800;color:#304858}.poll-detail-secondary{font-size:10px;color:var(--d-muted);margin-top:4px}.poll-detail-answer{white-space:pre-line;overflow-wrap:anywhere;min-width:180px;max-width:420px;line-height:1.9;color:#3e5361}
.poll-detail-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;padding:17px 22px;border-top:1px solid #edf1f4;background:#fcfdfd}
.poll-detail-close{border:1px solid #f0d8d4;border-radius:11px;padding:10px 14px;background:#fff6f5;color:#ad4a43;font-size:11px;font-weight:800;cursor:pointer}
.poll-detail-back{display:inline-flex;align-items:center;gap:7px;border:1px solid #d4e7e0;border-radius:11px;padding:10px 14px;background:#f2faf7;color:#176b63;font-size:11px;font-weight:800;text-decoration:none}
@media(max-width:900px){.poll-detail-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:580px){.poll-detail-stats{gap:8px}.poll-detail-stat{padding:13px}.poll-detail-stat strong{font-size:20px}.poll-detail-panel-head,.poll-detail-panel-body{padding:16px}.poll-detail-hero{border-radius:19px}.poll-detail-footer{padding:14px 16px}}
</style>
<div class="poll-detail">
    <section class="poll-detail-hero">
        <div class="poll-detail-topline">
            <a class="poll-back" href="{{ route('polls.index') }}">→ بازگشت به همه نظرسنجی‌ها</a>
            <span class="poll-detail-status">{{ $ended ? '● پایان‌یافته' : (!$started ? '◷ هنوز شروع نشده' : '● در حال رأی‌گیری') }}</span>
        </div>
        <h2>{{ $poll->title }}</h2>
        @if($poll->description)<p>{{ $poll->description }}</p>@else<p>نمای کلی نظرسنجی، آمار مشارکت و پاسخ‌های ثبت‌شده در این بخش قابل بررسی است.</p>@endif
        <div class="poll-detail-hero-meta">
            <span>{{ $poll->response_type === 'text' ? '✎ پاسخ تشریحی' : '◉ رأی‌گیری گزینه‌ای' }}</span>
            <span>{{ $poll->audience === 'residents' ? '⌂ ویژه مالک و مستأجر' : '♧ تمام اعضای مجاز' }}</span>
            <span>{{ $poll->is_anonymous ? '▣ رأی محرمانه' : '♙ هویت غیرمحرمانه' }}</span>
        </div>
    </section>

    <section class="poll-detail-stats" aria-label="آمار نظرسنجی">
        <div class="poll-detail-stat"><div class="poll-detail-stat-top"><span>مجموع مشارکت</span><span class="poll-detail-stat-icon">◉</span></div><strong>{{ $F::digits($total) }}</strong><small>{{ $poll->response_type === 'text' ? 'پاسخ ثبت‌شده' : 'رأی ثبت‌شده' }}</small></div>
        <div class="poll-detail-stat"><div class="poll-detail-stat-top"><span>نوع نظرسنجی</span><span class="poll-detail-stat-icon">☷</span></div><strong style="font-size:18px">{{ $poll->response_type === 'text' ? 'تشریحی' : 'گزینه‌ای' }}</strong><small>شیوه دریافت پاسخ</small></div>
        <div class="poll-detail-stat"><div class="poll-detail-stat-top"><span>زمان باقی‌مانده</span><span class="poll-detail-stat-icon">◷</span></div><strong style="font-size:18px">{{ $ended ? 'تمام شده' : ($started ? $poll->ends_at->diffForHumans() : $poll->starts_at->diffForHumans()) }}</strong><small>{{ $started ? 'تا پایان رأی‌گیری' : 'تا شروع رأی‌گیری' }}</small></div>
        <div class="poll-detail-stat"><div class="poll-detail-stat-top"><span>وضعیت حریم خصوصی</span><span class="poll-detail-stat-icon">▣</span></div><strong style="font-size:18px">{{ $poll->is_anonymous ? 'محرمانه' : 'عادی' }}</strong><small>{{ $poll->is_anonymous ? 'هویت شرکت‌کنندگان پنهان است' : 'نمایش جزئیات مجاز است' }}</small></div>
    </section>

    <section class="poll-detail-panel">
        <div class="poll-detail-panel-head">
            <div class="poll-detail-panel-title"><span class="poll-detail-panel-icon">◷</span><div><h3>اطلاعات و زمان‌بندی</h3><p>جزئیات اجرایی این نظرسنجی</p></div></div>
        </div>
        <div class="poll-detail-panel-body">
            <div class="poll-detail-stats" style="margin:0;grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr))">
                <div class="poll-detail-stat"><div class="poll-detail-stat-top"><span>شروع رأی‌گیری</span></div><strong style="font-size:16px">{{ $poll->starts_at->format('Y/m/d') }}</strong><small>{{ $F::digits($poll->starts_at->format('H:i')) }}</small></div>
                <div class="poll-detail-stat"><div class="poll-detail-stat-top"><span>پایان رأی‌گیری</span></div><strong style="font-size:16px">{{ $poll->ends_at->format('Y/m/d') }}</strong><small>{{ $F::digits($poll->ends_at->format('H:i')) }}</small></div>
                <div class="poll-detail-stat"><div class="poll-detail-stat-top"><span>ایجادکننده</span></div><strong style="font-size:16px">{{ $poll->createdBy?->display_name ?: '—' }}</strong><small>مدیر ثبت‌کننده</small></div>
                <div class="poll-detail-stat"><div class="poll-detail-stat-top"><span>مخاطبان</span></div><strong style="font-size:15px">{{ $poll->audience === 'residents' ? 'مالک و مستأجر' : 'اعضای مجاز' }}</strong><small>دامنه مشارکت</small></div>
            </div>
        </div>
    </section>

    <section class="poll-detail-panel">
        <div class="poll-detail-panel-head">
            <div class="poll-detail-panel-title"><span class="poll-detail-panel-icon">▥</span><div><h3>{{ $poll->is_anonymous ? 'خلاصه نتایج' : 'نتایج و شرکت‌کنندگان' }}</h3><p>بررسی توزیع آرا و وضعیت مشارکت</p></div></div>
            <span style="font-size:11px;color:var(--d-muted)">{{ $F::digits($total) }} {{ $poll->response_type === 'text' ? 'پاسخ' : 'رأی' }}</span>
        </div>
        <div class="poll-detail-panel-body">
            @if($poll->response_type === 'choice')
                @if($poll->options->isEmpty())
                    <div class="poll-detail-empty"><strong>گزینه‌ای برای نمایش وجود ندارد</strong><p>این نظرسنجی هنوز گزینه‌ای ندارد.</p></div>
                @else
                    <div class="poll-detail-results">
                        @foreach($poll->options as $option)
                            @php $count = (int) $option->votes_count; $percent = $total > 0 ? (int) round($count * 100 / $total) : 0; @endphp
                            <div>
                                <div class="poll-detail-result-head"><span class="poll-detail-result-name">{{ $option->text }}</span><span class="poll-detail-result-count">{{ $F::digits($count) }} رأی</span></div>
                                <div class="poll-detail-track" role="progressbar" aria-label="درصد آرای {{ $option->text }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}"><div class="poll-detail-fill" style="width:{{ $percent }}%"></div></div>
                                <div class="poll-detail-result-foot"><span>سهم از کل آرا</span><strong>{{ $F::digits($percent) }}٪</strong></div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
            @if($poll->is_anonymous)
                <div class="poll-detail-privacy" style="margin-top:18px"><span class="poll-detail-privacy-icon">▣</span><span><strong>حریم خصوصی حفظ می‌شود.</strong><br>این نظرسنجی مخفی است؛ هویت و پاسخ منتسب به هر فرد نمایش داده نمی‌شود. آمار تجمیعی بالا همچنان قابل مشاهده است.</span></div>
            @elseif($poll->votes->isEmpty())
                <div class="poll-detail-empty" style="margin-top:16px"><div style="font-size:24px">◉</div><strong>هنوز پاسخی ثبت نشده است</strong><p>به‌محض مشارکت اعضا، پاسخ‌ها و جزئیات آن‌ها در این بخش نمایش داده می‌شود.</p></div>
            @else
                <div style="margin-top:20px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap"><strong style="font-size:13px">فهرست پاسخ‌ها</strong><span style="font-size:11px;color:var(--d-muted)">{{ $F::digits($poll->votes->count()) }} شرکت‌کننده</span></div>
                <div class="poll-detail-table-wrap" style="margin:12px -22px -21px">
                    <table class="poll-detail-table">
                        <thead><tr><th>شرکت‌کننده</th><th>{{ $poll->response_type === 'text' ? 'پاسخ تشریحی' : 'گزینه انتخاب‌شده' }}</th><th>زمان ثبت</th></tr></thead>
                        <tbody>
                        @foreach($poll->votes->sortBy('created_at') as $vote)
                            <tr><td><div class="poll-detail-participant">{{ $vote->user?->display_name ?: 'کاربر حذف‌شده' }}</div><div class="poll-detail-secondary">{{ $vote->user?->mobile }}</div></td><td class="poll-detail-answer">{{ $poll->response_type === 'text' ? $vote->answer : ($vote->option?->text ?: '—') }}</td><td style="white-space:nowrap">{{ $vote->created_at?->format('Y/m/d') }}<div class="poll-detail-secondary">{{ $F::digits($vote->created_at?->format('H:i') ?? '') }}</div></td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        @if(!$ended)
            <div class="poll-detail-footer">
                <span style="font-size:11px;color:var(--d-muted);line-height:1.9">با بستن نظرسنجی، امکان ثبت رأی جدید متوقف می‌شود.</span>
                <form method="POST" action="{{ route('polls.close', $poll->id) }}" data-confirm="این رأی‌گیری بسته شود؟" data-confirm-title="بستن رأی‌گیری" data-confirm-icon="warning">
                    @csrf<button class="poll-detail-close" type="submit">بستن رأی‌گیری</button>
                </form>
            </div>
        @endif
    </section>
    <div style="display:flex;justify-content:flex-start;margin-bottom:10px"><a class="poll-detail-back" href="{{ route('polls.index') }}">→ بازگشت به نظرسنجی‌ها</a></div>
</div>
@endsection
