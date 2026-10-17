@php($F = \\App\\Support\\Fa::class)
@extends('layouts.app')
@section('title', 'نظرسنجی و رأی‌گیری')
@section('content')
<style>
.poll-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,340px),1fr));gap:16px}
.poll-card{border:1px solid var(--line,#e5e7eb);border-radius:16px;padding:18px;background:var(--card,#fff);min-width:0}
.poll-head{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}
.poll-title{margin:0 0 8px;font-size:17px}
.poll-meta{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0;color:var(--muted,#667085);font-size:12px}
.poll-chip{border-radius:999px;padding:4px 9px;background:var(--soft,#f1f5f9)}
.poll-options{display:grid;gap:9px;margin:14px 0}
.poll-option{display:flex;align-items:center;gap:9px;padding:10px 12px;border:1px solid var(--line,#e5e7eb);border-radius:10px;cursor:pointer}
.poll-option input{accent-color:var(--primary,#1f6a63);flex-shrink:0}
.poll-result{margin:12px 0}
.poll-result-top{display:flex;justify-content:space-between;gap:8px;font-size:13px;margin-bottom:5px}
.poll-track{height:8px;background:var(--soft,#edf2f7);border-radius:99px;overflow:hidden}
.poll-fill{height:100%;background:var(--primary,#1f6a63);border-radius:99px}
.poll-form-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:12px}
.poll-description{white-space:pre-line;color:var(--muted,#667085);margin:8px 0}
.poll-footer{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
</style>
@if($canManage)
<div class="card">
  <div class="page-h"><h2>ایجاد نظرسنجی جدید</h2></div>
  <form method="POST" action="{{ route('polls.store') }}" style="margin-top:12px">@csrf
    <div class="poll-form-grid">
      <div class="fld"><label for="poll-title">عنوان نظرسنجی *</label><input id="poll-title" name="title" value="{{ old('title') }}" maxlength="200" required placeholder="مثلاً انتخاب زمان تعمیر آسانسور">@error('title')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="poll-audience">مخاطبان</label><select id="poll-audience" name="audience"><option value="all" @selected(old('audience','all')==='all')>تمام اعضای مجاز</option><option value="residents" @selected(old('audience')==='residents')>فقط مالک واحد و مستأجر</option></select></div>
      <div class="fld"><label for="poll-start">شروع رأی‌گیری *</label><input id="poll-start" type="datetime-local" name="starts_at" value="{{ old('starts_at', now()->format('Y-m-d\\TH:i')) }}" required>@error('starts_at')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="poll-end">پایان رأی‌گیری *</label><input id="poll-end" type="datetime-local" name="ends_at" value="{{ old('ends_at', now()->addDays(7)->format('Y-m-d\\TH:i')) }}" required>@error('ends_at')<div class="e">{{ $message }}</div>@enderror</div>
    </div>
    <div class="fld" style="margin-top:12px"><label for="poll-description">توضیحات (اختیاری)</label><textarea id="poll-description" name="description" rows="3" maxlength="2000" placeholder="جزئیات لازم برای تصمیم‌گیری اعضا">{{ old('description') }}</textarea>@error('description')<div class="e">{{ $message }}</div>@enderror</div>
    <div class="poll-form-grid" style="margin-top:12px">
      @for($i=0;$i<4;$i++)
      <div class="fld"><label for="poll-option-{{ $i+1 }}">گزینه {{ $F::digits($i+1) }} @if($i<2)*@endif</label><input id="poll-option-{{ $i+1 }}" name="options[]" value="{{ old('options.'.$i) }}" maxlength="200" @if($i<2) required @endif placeholder="متن گزینه"></div>
      @endfor
    </div>
    @error('options')<div class="e" style="margin-top:8px">{{ $message }}</div>@enderror
    @error('options.*')<div class="e" style="margin-top:8px">{{ $message }}</div>@enderror
    <label class="poll-option" style="margin-top:12px"><input type="checkbox" name="is_anonymous" value="1" @checked(old('is_anonymous'))> رأی‌گیری مخفی باشد (هویت رأی‌دهندگان در نتایج نمایش داده نمی‌شود)</label>
    <div class="fbar"><button class="btn" type="submit">ایجاد نظرسنجی</button></div>
  </form>
</div>
@endif

<div class="card">
  <div class="page-h"><h2>نظرسنجی‌های ساختمان</h2><span class="poll-chip">{{ $F::digits($polls->total()) }} مورد</span></div>
  @if($polls->isEmpty())
    <div class="empty">هنوز نظرسنجی‌ای ثبت نشده است.</div>
  @else
    <div class="poll-grid" style="margin-top:14px">
      @foreach($polls as $poll)
        @php
          $ended = $poll->status !== 'open' || now()->gte($poll->ends_at);
          $started = now()->gte($poll->starts_at);
          $hasVoted = $poll->votes->isNotEmpty();
          $showResults = $canManage || $ended || $hasVoted;
          $residentOnly = $poll->audience === 'residents';
          $eligibleRole = count(array_intersect($currentRoles, ['tenant','unit_owner'])) > 0;
        @endphp
        <article class="poll-card">
          <div class="poll-head"><h3 class="poll-title">{{ $poll->title }}</h3><span class="poll-chip">{{ $ended ? 'پایان‌یافته' : ($started ? 'در حال رأی‌گیری' : 'شروع‌نشده') }}</span></div>
          @if($poll->description)<p class="poll-description">{{ $poll->description }}</p>@endif
          <div class="poll-meta"><span>{{ $F::digits($poll->votes_count) }} رأی</span><span>{{ $residentOnly ? 'مخصوص ساکنان' : 'تمام اعضای مجاز' }}</span>@if($poll->is_anonymous)<span>مخفی</span>@endif</div>

          @if($showResults)
            @foreach($poll->options as $option)
              @php($percent = $poll->votes_count > 0 ? (int) round($option->votes_count * 100 / $poll->votes_count) : 0)
              <div class="poll-result">
                <div class="poll-result-top"><span>{{ $option->text }}</span><b>{{ $F::digits($percent) }}٪ <small>({{ $F::digits($option->votes_count) }})</small></b></div>
                <div class="poll-track"><div class="poll-fill" style="width:{{ $percent }}%"></div></div>
              </div>
            @endforeach
          @else
            @if($canVote && $started && !$ended && (!$residentOnly || $eligibleRole))
              <form method="POST" action="{{ route('polls.vote', $poll->id) }}">@csrf
                <div class="poll-options">
                  @foreach($poll->options as $option)
                    <label class="poll-option"><input type="radio" name="option_id" value="{{ $option->id }}" required><span>{{ $option->text }}</span></label>
                  @endforeach
                </div>
                <button class="btn" type="submit">ثبت رأی</button>
              </form>
            @elseif($hasVoted)
              <p class="poll-description">رأی شما ثبت شده است.</p>
            @elseif(!$started)
              <p class="poll-description">رأی‌گیری از {{ $poll->starts_at->format('Y/m/d H:i') }} شروع می‌شود.</p>
            @elseif($residentOnly && !$eligibleRole)
              <p class="poll-description">این نظرسنجی فقط برای مالک واحد و مستأجر است.</p>
            @else
              <p class="poll-description">برای رأی دادن به این نظرسنجی دسترسی ندارید.</p>
            @endif
          @endif

          @if($canManage && !$ended)
            <div class="poll-footer"><form method="POST" action="{{ route('polls.close', $poll->id) }}" data-confirm="این رأی‌گیری بسته شود؟" data-confirm-title="بستن رأی‌گیری" data-confirm-icon="warning">@csrf<button class="btn dng sm" type="submit">بستن رأی‌گیری</button></form></div>
          @endif
        </article>
      @endforeach
    </div>
    <div style="margin-top:16px">{{ $polls->links() }}</div>
  @endif
</div>
@endsection
