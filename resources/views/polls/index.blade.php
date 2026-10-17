@php
    $F = \App\Support\Fa::class;
@endphp
@extends('layouts.app')
@section('title', 'نظرسنجی و رأی‌گیری')
@section('content')
<style>
.poll-page{--p-ink:#172b3a;--p-muted:#718096;--p-line:#e3eaf0;--p-teal:#176b63;--p-mint:#e9f7f2;color:var(--p-ink)}
.poll-hero{position:relative;isolation:isolate;overflow:hidden;border-radius:24px;padding:clamp(22px,4vw,36px);margin-bottom:20px;color:#fff;background:radial-gradient(circle at 8% 12%,rgba(145,236,208,.25),transparent 30%),linear-gradient(125deg,#123d42 0%,#176b63 52%,#398d83 100%);box-shadow:0 18px 40px rgba(23,92,85,.18)}
.poll-hero:after{content:"";position:absolute;z-index:-1;width:250px;height:250px;border:1px solid #ffffff26;border-radius:50%;inset-inline-end:-75px;top:-125px;box-shadow:0 0 0 28px #ffffff08,0 0 0 58px #ffffff08}
.poll-hero-inner{display:flex;justify-content:space-between;align-items:center;gap:22px;flex-wrap:wrap}
.poll-eyebrow{display:inline-flex;align-items:center;gap:7px;border:1px solid #ffffff35;background:#ffffff14;border-radius:999px;padding:6px 11px;font-size:11px;color:#e0fff4;margin-bottom:14px}
.poll-hero h2{font-size:clamp(23px,3vw,31px);line-height:1.6;font-weight:850;color:#fff;margin:0 0 8px;letter-spacing:-.4px}
.poll-hero p{max-width:650px;color:#d9f0e9;font-size:13px;line-height:2;margin:0}
.poll-hero-stat{min-width:126px;padding:17px 21px;border:1px solid #ffffff35;background:#ffffff14;border-radius:18px;text-align:center;backdrop-filter:blur(8px)}
.poll-hero-stat strong{display:block;font-size:30px;color:#fff;line-height:1.3}.poll-hero-stat span{font-size:11px;color:#d9f0e9}
.poll-section{background:var(--card,#fff);border:1px solid var(--p-line);border-radius:21px;overflow:hidden;margin-bottom:18px;box-shadow:0 8px 25px #1b3b4a08}
.poll-section-head{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:20px 23px;border-bottom:1px solid var(--p-line);background:linear-gradient(180deg,#fff,#fbfdfd)}
.poll-section-title{display:flex;align-items:center;gap:12px}.poll-section-icon{display:grid;place-items:center;width:42px;height:42px;border-radius:14px;background:#e8f6f1;color:var(--p-teal);font-size:20px}
.poll-section-head h3{font-size:16px;font-weight:850;margin:0 0 4px;color:var(--p-ink)}.poll-section-head p{font-size:11px;color:var(--p-muted);margin:0;line-height:1.8}
.poll-count{border-radius:999px;background:#edf7f4;color:#176b63;font-weight:800;font-size:11px;padding:6px 10px;white-space:nowrap}
.poll-create-body{padding:22px 23px}
.poll-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.poll-page .fld{min-width:0}.poll-page .fld label{display:block;font-size:12px;font-weight:750;color:#43576a;margin-bottom:7px}
.poll-page .fld input:not([type=checkbox]):not([type=radio]),.poll-page .fld select,.poll-page .fld textarea{width:100%;min-width:0;border:1px solid #dce5eb;border-radius:12px;padding:11px 13px;background:#fff;color:#203748;outline:none;transition:border-color .18s,box-shadow .18s;font:inherit;font-size:12px}
.poll-page .fld input:focus,.poll-page .fld select:focus,.poll-page .fld textarea:focus{border-color:#4b9e8d;box-shadow:0 0 0 4px #329b8118}
.poll-page .fld textarea{resize:vertical;line-height:1.9}
.poll-field-help{font-size:11px;color:var(--p-muted);line-height:1.8;margin:6px 0 0}
.poll-options-builder{margin-top:16px;padding:16px;border:1px solid #e1ece7;border-radius:16px;background:#f9fcfb}
.poll-options-builder-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:12px;font-size:12px;font-weight:800}
.poll-options-builder-head span:last-child{font-size:10px;font-weight:500;color:var(--p-muted)}
.poll-options-builder .poll-form-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
.poll-anonymous{display:flex;align-items:flex-start;gap:10px;margin-top:16px;padding:13px 14px;border:1px solid #e0e9ee;border-radius:13px;background:#fbfdfd;cursor:pointer}
.poll-anonymous input{margin-top:3px;accent-color:var(--p-teal);flex:0 0 auto;width:16px;height:16px}
.poll-anonymous strong{display:block;font-size:12px;color:#304858;margin-bottom:3px}.poll-anonymous small{display:block;font-size:11px;line-height:1.8;color:var(--p-muted)}
.poll-create-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:19px;padding-top:18px;border-top:1px solid #edf1f4}
.poll-submit{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:12px;padding:12px 19px;background:linear-gradient(120deg,#176b63,#2b9280);color:white;font-size:12px;font-weight:800;cursor:pointer;box-shadow:0 7px 16px #176b6326}
.poll-create-note{font-size:11px;color:var(--p-muted);line-height:1.8}
.poll-list-head{padding:20px 23px 0}
.poll-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,330px),1fr));gap:15px;padding:18px 20px 21px}
.poll-card{position:relative;display:flex;flex-direction:column;min-width:0;padding:19px;border:1px solid var(--p-line);border-radius:18px;background:var(--card,#fff);transition:transform .18s,border-color .18s,box-shadow .18s}
.poll-card:hover{transform:translateY(-2px);border-color:#c4ded6;box-shadow:0 13px 30px #1c51400d}
.poll-card-top{display:flex;align-items:flex-start;gap:12px}
.poll-card-symbol{display:grid;place-items:center;width:43px;height:43px;flex:0 0 auto;border-radius:14px;background:linear-gradient(145deg,#e8f6f1,#edf5ff);color:#237a6e;font-size:20px}
.poll-card-main{flex:1;min-width:0}.poll-title{font-size:15px;line-height:1.8;font-weight:850;color:var(--p-ink);margin:0 0 5px;overflow-wrap:anywhere}.poll-title a{color:inherit;text-decoration:none}.poll-title a:hover{color:var(--p-teal)}
.poll-chip{display:inline-flex;align-items:center;gap:5px;border-radius:999px;padding:5px 9px;background:#eef2f5;color:#526575;font-size:10px;font-weight:750;white-space:nowrap}
.poll-chip.live{background:#e7f7ee;color:#18764a}.poll-chip.wait{background:#fff4df;color:#94600b}.poll-chip.closed{background:#edf0f3;color:#647181}
.poll-description{white-space:pre-line;color:var(--p-muted);font-size:12px;line-height:1.9;margin:12px 0 0}
.poll-meta{display:flex;flex-wrap:wrap;gap:7px;margin:14px 0;padding-bottom:13px;border-bottom:1px solid #edf1f4;color:#647687;font-size:10px}
.poll-meta span{display:inline-flex;align-items:center;gap:5px;padding:5px 8px;border-radius:8px;background:#f4f7f8}
.poll-card-content{flex:1}.poll-options{display:grid;gap:8px;margin:13px 0}
.poll-option{display:flex;align-items:center;gap:10px;padding:11px 12px;border:1px solid #e2e9ed;border-radius:12px;background:#fff;cursor:pointer;transition:border-color .15s,background .15s;font-size:12px;color:#344b5a}
.poll-option:hover{border-color:#a6d4c7;background:#f8fcfa}.poll-option:has(input:checked){border-color:#70b9a5;background:#eff9f5;color:#176b63}
.poll-option input{accent-color:var(--p-teal);flex-shrink:0;width:16px;height:16px}
.poll-page .fld{margin-top:12px}
.poll-track{height:8px;background:#edf2f3;border-radius:99px;overflow:hidden}.poll-fill{height:100%;background:linear-gradient(90deg,#176b63,#55b89d);border-radius:99px}
.poll-result{margin:13px 0}.poll-result-top{display:flex;justify-content:space-between;gap:10px;font-size:12px;margin-bottom:7px;color:#344b5a}.poll-result-top b{white-space:nowrap;color:#176b63;font-size:11px}
.poll-footer{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-top:15px}
.poll-action{display:inline-flex;align-items:center;justify-content:center;gap:7px;border:1px solid #cfe4dd;border-radius:11px;padding:9px 12px;background:#f2faf7;color:#176b63;font-size:11px;font-weight:800;text-decoration:none;cursor:pointer}
.poll-action.danger{background:#fff6f5;color:#ad4a43;border-color:#f0d8d4}
.poll-vote-submit{width:100%;border:0;border-radius:12px;padding:12px;background:linear-gradient(120deg,#176b63,#2b9280);color:#fff;font-size:12px;font-weight:800;cursor:pointer;margin-top:5px}
.poll-state-note{display:flex;align-items:flex-start;gap:9px;padding:11px 12px;border-radius:12px;background:#f5f8f9;color:#617381;font-size:11px;line-height:1.9;margin-top:12px}
.poll-state-note .dot{display:grid;place-items:center;flex:0 0 auto;width:22px;height:22px;border-radius:8px;background:#e6f0ee;color:#277b6e;font-weight:800}
.poll-empty{margin:18px 20px 22px;padding:35px 20px;text-align:center;border:1px dashed #cfddd9;border-radius:17px;background:#fbfdfc;color:var(--p-muted)}
.poll-empty-icon{display:grid;place-items:center;width:52px;height:52px;margin:0 auto 12px;border-radius:17px;background:#eaf6f1;color:#287d6e;font-size:24px}
.poll-empty strong{display:block;color:#304858;font-size:14px;margin-bottom:5px}.poll-empty p{font-size:12px;line-height:1.9;margin:0}
.poll-errors{padding:12px 14px;margin-bottom:15px;border:1px solid #f2d4d0;border-radius:12px;background:#fff7f6;color:#a3433c;font-size:12px;line-height:1.9}
@media(max-width:680px){.poll-form-grid,.poll-options-builder .poll-form-grid{grid-template-columns:1fr}.poll-section-head{padding:17px}.poll-create-body{padding:17px}.poll-grid{grid-template-columns:1fr;padding:12px}.poll-list-head{padding:17px 17px 0}.poll-hero{border-radius:19px}.poll-hero-stat{min-width:100px;padding:12px 15px}.poll-hero-stat strong{font-size:25px}.poll-create-actions{align-items:stretch}.poll-submit{width:100%}.poll-create-note{order:2}}
</style>
<div class="poll-page">
    <section class="poll-hero">
        <div class="poll-hero-inner">
            <div>
                <div class="poll-eyebrow">✦ مرکز مشارکت و تصمیم‌گیری ساختمان</div>
                <h2>صدای همه، تصمیم بهتر برای ساختمان</h2>
                <p>نظرات و رأی اعضا را در یک فضای شفاف و منظم جمع‌آوری کنید؛ از انتخاب‌های روزمره تا تصمیم‌های مهم ساختمان.</p>
            </div>
            <div class="poll-hero-stat"><strong>{{ $F::digits($polls->total()) }}</strong><span>نظرسنجی ثبت‌شده</span></div>
        </div>
    </section>

    @if(session('status'))<div class="poll-state-note" role="status" style="margin-bottom:15px"><span class="dot">✓</span>{{ session('status') }}</div>@endif
    @if($errors->any())<div class="poll-errors" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

    @if($canManage)
    <section class="poll-section">
        <div class="poll-section-head">
            <div class="poll-section-title"><span class="poll-section-icon">＋</span><div><h3>ساخت نظرسنجی تازه</h3><p>در چند قدم کوتاه، موضوع را تعریف و برای اعضا منتشر کنید.</p></div></div>
            <span class="poll-count">فرم ایجاد</span>
        </div>
        <div class="poll-create-body">
            <form method="POST" action="{{ route('polls.store') }}">
                @csrf
                <div class="poll-form-grid">
                    <div class="fld">
                        <label for="poll-title">عنوان نظرسنجی <span aria-hidden="true">*</span></label>
                        <input id="poll-title" name="title" value="{{ old('title') }}" maxlength="200" required placeholder="مثلاً انتخاب زمان سرویس آسانسور">
                        @error('title')<div class="e">{{ $message }}</div>@enderror
                    </div>
                    <div class="fld">
                        <label for="poll-audience">چه کسانی شرکت کنند؟</label>
                        <select id="poll-audience" name="audience">
                            <option value="all" @selected(old('audience','all')==='all')>تمام اعضای مجاز ساختمان</option>
                            <option value="residents" @selected(old('audience')==='residents')>فقط مالک واحد و مستأجر</option>
                        </select>
                        <p class="poll-field-help">مخاطبان مجاز بر اساس نقش‌های ساختمان تعیین می‌شوند.</p>
                    </div>
                    <div class="fld">
                        <label for="poll-start-date">زمان شروع <span aria-hidden="true">*</span></label>
                        <input id="poll-start-date" class="jdate" data-min="1300/01/01" name="starts_date" value="{{ $startDate }}" placeholder="انتخاب تاریخ شمسی" autocomplete="off" required>
                        <input id="poll-start-time" type="time" name="starts_time" value="{{ $startTime }}" required>
                        @error('starts_at')<div class="e">{{ $message }}</div>@enderror
                    </div>
                    <div class="fld">
                        <label for="poll-end-date">مهلت پایان <span aria-hidden="true">*</span></label>
                        <input id="poll-end-date" class="jdate" data-min="1300/01/01" name="ends_date" value="{{ $endDate }}" placeholder="انتخاب تاریخ شمسی" autocomplete="off" required>
                        <input id="poll-end-time" type="time" name="ends_time" value="{{ $endTime }}" required>
                        @error('ends_at')<div class="e">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="fld" style="margin-top:15px">
                    <label for="poll-description">توضیحات و زمینه تصمیم‌گیری</label>
                    <textarea id="poll-description" name="description" rows="3" maxlength="2000" placeholder="توضیح کوتاهی درباره موضوع، گزینه‌ها یا دلیل این نظرسنجی بنویسید...">{{ old('description') }}</textarea>
                    @error('description')<div class="e">{{ $message }}</div>@enderror
                </div>
                <div class="fld" style="margin-top:15px;max-width:460px">
                    <label for="poll-response-type">شیوه دریافت پاسخ</label>
                    <select id="poll-response-type" name="response_type">
                        <option value="choice" @selected(old('response_type', 'choice') === 'choice')>انتخاب از بین گزینه‌ها</option>
                        <option value="text" @selected(old('response_type') === 'text')>پاسخ تشریحی و نوشتاری</option>
                    </select>
                    <p class="poll-field-help">برای تصمیم‌گیری سریع، گزینه‌ای؛ برای دریافت دیدگاه آزاد، تشریحی را انتخاب کنید.</p>
                </div>
                <div id="poll-choice-options" class="poll-options-builder">
                    <div class="poll-options-builder-head"><span>گزینه‌های پاسخ</span><span>حداقل ۲ گزینه، حداکثر ۸ گزینه</span></div>
                    <div class="poll-form-grid">
                        @for($i=0;$i<4;$i++)
                        <div class="fld">
                            <label for="poll-option-{{ $i+1 }}">گزینه {{ $F::digits($i+1) }} @if($i<2)<span aria-hidden="true">*</span>@endif</label>
                            <input id="poll-option-{{ $i+1 }}" name="options[]" value="{{ old('options.'.$i) }}" maxlength="200" @if($i<2) required @endif placeholder="{{ $i===0 ? 'مثلاً شنبه ساعت ۱۰' : ($i===1 ? 'مثلاً یکشنبه ساعت ۱۶' : 'گزینه دیگر (اختیاری)') }}">
                        </div>
                        @endfor
                    </div>
                    @error('options')<div class="e" style="margin-top:8px">{{ $message }}</div>@enderror
                </div>
                <script>
                    (() => {
                        const type = document.getElementById('poll-response-type');
                        const options = document.getElementById('poll-choice-options');
                        if (!type || !options) return;
                        const sync = () => {
                            const choice = type.value === 'choice';
                            options.hidden = !choice;
                            options.querySelectorAll('input').forEach((input, index) => {
                                input.required = choice && index < 2;
                                input.disabled = !choice;
                            });
                        };
                        type.addEventListener('change', sync);
                        sync();
                    })();
                </script>
                <label class="poll-anonymous">
                    <input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous'))>
                    <span><strong>رأی‌گیری محرمانه باشد</strong><small>هویت و پاسخ منتسب به هر شرکت‌کننده در صفحه نتایج مدیر نمایش داده نمی‌شود.</small></span>
                </label>
                <div class="poll-create-actions">
                    <span class="poll-create-note">پیش از ایجاد، زمان شروع و پایان و مخاطبان را بررسی کنید.</span>
                    <button class="poll-submit" type="submit">✦ ایجاد نظرسنجی</button>
                </div>
            </form>
        </div>
    </section>
    @endif

    <section class="poll-section">
        <div class="poll-list-head">
            <div class="poll-section-head" style="padding:0 0 16px;border:0;background:transparent">
                <div class="poll-section-title"><span class="poll-section-icon">☷</span><div><h3>نظرسنجی‌های ساختمان</h3><p>وضعیت، مشارکت و نتیجه نظرسنجی‌ها را دنبال کنید.</p></div></div>
                <span class="poll-count">{{ $F::digits($polls->total()) }} مورد</span>
            </div>
        </div>
        @if($polls->isEmpty())
            <div class="poll-empty"><div class="poll-empty-icon">☷</div><strong>هنوز نظرسنجی‌ای ثبت نشده است</strong><p>با ایجاد اولین نظرسنجی، زمینه مشارکت و تصمیم‌گیری بهتر در ساختمان را فراهم کنید.</p></div>
        @else
            <div class="poll-grid">
                @foreach($polls as $poll)
                    @php
                        $ended = $poll->status !== 'open' || now()->gte($poll->ends_at);
                        $started = now()->gte($poll->starts_at);
                        $hasVoted = $poll->votes->isNotEmpty();
                        $showResults = $canManage || $ended || $hasVoted;
                        $residentOnly = $poll->audience === 'residents';
                        $eligibleRole = count(array_intersect($currentRoles, ['tenant', 'unit_owner'])) > 0;
                    @endphp
                    <article class="poll-card">
                        <div class="poll-card-top">
                            <div class="poll-card-symbol">{{ $poll->response_type === 'text' ? '✎' : '◉' }}</div>
                            <div class="poll-card-main">
                                <h3 class="poll-title">@if($canManage)<a href="{{ route('polls.show', $poll->id) }}">{{ $poll->title }}</a>@else{{ $poll->title }}@endif</h3>
                                <span class="poll-chip {{ $ended ? 'closed' : (!$started ? 'wait' : 'live') }}">
                                    <span aria-hidden="true">{{ $ended ? '●' : (!$started ? '◷' : '●') }}</span>
                                    {{ $ended ? 'پایان‌یافته' : (!$started ? 'شروع‌نشده' : 'در حال رأی‌گیری') }}
                                </span>
                            </div>
                        </div>
                        @if($poll->description)<p class="poll-description">{{ $poll->description }}</p>@endif
                        <div class="poll-meta">
                            <span>◉ {{ $F::digits($poll->votes_count) }} {{ $poll->response_type === 'text' ? 'پاسخ' : 'رأی' }}</span>
                            <span>{{ $poll->response_type === 'text' ? 'تشریحی' : 'گزینه‌ای' }}</span>
                            <span>{{ $residentOnly ? 'مالک و مستأجر' : 'همه اعضای مجاز' }}</span>
                            @if($poll->is_anonymous)<span>محرمانه</span>@endif
                        </div>
                        <div class="poll-card-content">
                            @if($showResults)
                                @if($poll->response_type === 'choice')
                                    @foreach($poll->options as $option)
                                        @php $percent = $poll->votes_count > 0 ? (int) round($option->votes_count * 100 / $poll->votes_count) : 0; @endphp
                                        <div class="poll-result">
                                            <div class="poll-result-top"><span>{{ $option->text }}</span><b>{{ $F::digits($percent) }}٪ <small>({{ $F::digits($option->votes_count) }})</small></b></div>
                                            <div class="poll-track"><div class="poll-fill" style="width:{{ $percent }}%"></div></div>
                                        </div>
                                    @endforeach
                                @elseif($hasVoted)
                                    <div class="poll-state-note"><span class="dot">✓</span>پاسخ شما ثبت شده است؛ نتایج پاسخ‌های تشریحی در این صفحه نمایش داده نمی‌شوند.</div>
                                @endif
                            @else
                                @if($canVote && $started && !$ended && (!$residentOnly || $eligibleRole))
                                    <form method="POST" action="{{ route('polls.vote', $poll->id) }}">
                                        @csrf
                                        @if($poll->response_type === 'text')
                                            <div class="fld">
                                                <label for="poll-answer-{{ $poll->id }}">دیدگاه شما</label>
                                                <textarea id="poll-answer-{{ $poll->id }}" name="answer" rows="4" maxlength="5000" required placeholder="نظر خود را با جزئیات بنویسید..."></textarea>
                                            </div>
                                        @else
                                            <div class="poll-options">
                                                @foreach($poll->options as $option)
                                                    <label class="poll-option"><input type="radio" name="option_id" value="{{ $option->id }}" required><span>{{ $option->text }}</span></label>
                                                @endforeach
                                            </div>
                                        @endif
                                        <button class="poll-vote-submit" type="submit">ثبت {{ $poll->response_type === 'text' ? 'پاسخ' : 'رأی' }} ←</button>
                                    </form>
                                @elseif($hasVoted)
                                    <div class="poll-state-note"><span class="dot">✓</span>پاسخ شما با موفقیت ثبت شده است.</div>
                                @elseif(!$started)
                                    <div class="poll-state-note"><span class="dot">◷</span>رأی‌گیری از {{ $F::digits(\\App\\Support\\JalaliDate::format($poll->starts_at)) }} ساعت {{ $F::digits($poll->starts_at->format('H:i')) }} آغاز می‌شود.</div>
                                @elseif($residentOnly && !$eligibleRole)
                                    <div class="poll-state-note"><span class="dot">i</span>شرکت در این نظرسنجی فقط برای مالک واحد و مستأجر مجاز است.</div>
                                @else
                                    <div class="poll-state-note"><span class="dot">i</span>برای شرکت در این نظرسنجی دسترسی لازم را ندارید.</div>
                                @endif
                            @endif
                        </div>
                        @if($canManage)
                            <div class="poll-footer">
                                <a class="poll-action" href="{{ route('polls.show', $poll->id) }}">مشاهده جزئیات و نتایج ←</a>
                                @if(!$ended)
                                    <form method="POST" action="{{ route('polls.close', $poll->id) }}" data-confirm="این رأی‌گیری بسته شود؟" data-confirm-title="بستن رأی‌گیری" data-confirm-icon="warning">
                                        @csrf<button class="poll-action danger" type="submit">بستن رأی‌گیری</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
            <div style="padding:0 20px 20px">{{ $polls->links() }}</div>
        @endif
    </section>
</div>
@endsection
