@extends('admin.layout')
@section('title', 'پیگیری تیکت '.$ticket->number)
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin-support.css') }}">@endpush
@section('content')
@php
    $F = \App\Support\Fa::class;
    $statusTone = ['open'=>'t-info','in_progress'=>'t-brand','waiting_customer'=>'t-warn','resolved'=>'t-ok','closed'=>'t-muted'];
    $priorityTone = ['low'=>'t-muted','normal'=>'t-info','high'=>'t-warn','urgent'=>'t-danger'];
    $categories = ['general'=>'عمومی','technical'=>'فنی','billing'=>'اشتراک و پرداخت','account'=>'حساب کاربری','suggestion'=>'پیشنهاد'];
    $finished = in_array($ticket->status, ['resolved','closed'], true);
    $customerName = $ticket->user?->display_name ?: $ticket->user?->mobile;
    $responseBreached = ! $ticket->first_response_at && $ticket->first_response_breached_at;
    $resolutionBreached = ! $finished && $ticket->resolution_breached_at;
    $initial = fn ($name) => \Illuminate\Support\Str::substr((string) $name, 0, 1) ?: '؟';
    $composerTab = old('_tab', 'reply');
@endphp
<div class="sp">
  @if($responseBreached || $resolutionBreached)
    <div class="sp-alert t-danger" role="alert">
      @if($responseBreached && $resolutionBreached) مهلت پاسخ اولیه و مهلت رسیدگی این تیکت نقض شده است.
      @elseif($responseBreached) مهلت پاسخ اولیه این تیکت نقض شده است.
      @else مهلت رسیدگی این تیکت نقض شده است. @endif
    </div>
  @endif

  <div class="sp-detail">
    <div class="sp-col">
      <section class="sp-card sp-ticket-head">
        <div class="sp-ticket-top">
          <span class="sp-num">{{ $ticket->number }}</span>
          <span class="sp-pill dot {{ $statusTone[$ticket->status] ?? 't-muted' }}">{{ $statuses[$ticket->status] ?? $ticket->status }}</span>
          <span class="sp-pill {{ $priorityTone[$ticket->priority] ?? 't-muted' }}">اولویت {{ $priorities[$ticket->priority] ?? $ticket->priority }}</span>
          <a class="sp-back" href="{{ route('admin.support.index') }}">بازگشت به فهرست ←</a>
        </div>
        <h2>{{ $ticket->subject }}</h2>
        <p>ثبت‌شده در {{ $F::date($ticket->created_at, 'j F Y · H:i') }} · {{ $F::num($ticket->messages->count()) }} پیام</p>
      </section>

      <div class="sp-thread">
        @foreach($ticket->messages as $message)
          @php
              $kind = $message->is_internal ? 'is-internal' : ($message->sender_type === 'admin' ? 'is-admin' : 'is-customer');
          @endphp
          <article class="sp-msg {{ $kind }}">
            <span class="sp-avatar" aria-hidden="true">
              @if($message->is_internal)
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
              @elseif($message->sender_type === 'admin') پ
              @else {{ $initial($customerName) }} @endif
            </span>
            <div class="sp-bubble">
              <header><strong>{{ $message->is_internal ? 'یادداشت داخلی · فقط کارشناسان' : ($message->sender_type==='admin'?'تیم پشتیبانی':'مشتری') }}</strong><time>{{ $F::date($message->created_at,'j F Y · H:i') }}</time></header>
              <p>{{ $message->body }}</p>
              @foreach($message->attachments as $attachment)<a class="sp-attach" href="{{ route('admin.support.attachments.download',$attachment) }}">📎 {{ $attachment->original_name }}</a>@endforeach
            </div>
          </article>
        @endforeach
      </div>

      <section class="sp-card" id="sp-composer" data-initial="{{ $composerTab === 'note' ? 'note' : 'reply' }}">
        <div class="sp-tabs" role="tablist" aria-label="نوع پیام">
          <button type="button" role="tab" data-tab="reply" aria-selected="true">پاسخ به مشتری</button>
          <button type="button" role="tab" data-tab="note" aria-selected="false">یادداشت داخلی</button>
        </div>

        <form class="sp-form" data-pane="reply" method="POST" enctype="multipart/form-data" action="{{ route('admin.support.reply',$ticket) }}">
          @csrf
          <input type="hidden" name="_tab" value="reply">
          <div class="fld"><label for="canned_reply">پاسخ آماده</label><select id="canned_reply"><option value="">انتخاب پاسخ آماده...</option>@foreach($cannedReplies as $canned)<option value="{{ e($canned->body) }}">{{ $canned->title }}</option>@endforeach</select></div>
          <div class="fld"><label for="body">پاسخ به مشتری</label><textarea id="body" name="body" required minlength="2" maxlength="10000" placeholder="پاسخ خود را بنویسید...">{{ $composerTab === 'reply' ? old('body') : '' }}</textarea>@if($composerTab === 'reply')@error('body')<div class="e">{{ $message }}</div>@enderror @endif</div>
          <div class="sp-form-row">
            <div class="fld"><label for="reply_status">وضعیت پس از پاسخ</label><select id="reply_status" name="status">@foreach(['in_progress'=>'در حال بررسی','waiting_customer'=>'منتظر پاسخ مشتری','resolved'=>'حل‌شده','closed'=>'بسته'] as $key=>$label)<option value="{{ $key }}" @selected(old('status','waiting_customer')===$key)>{{ $label }}</option>@endforeach</select></div>
            <div class="fld"><label for="reply_files">پیوست‌ها</label><input id="reply_files" class="sp-file" type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt"></div>
          </div>
          <div class="sp-form-foot"><span class="sp-help">حداکثر ۵ فایل، هرکدام تا ۱۰ مگابایت</span><button type="submit" class="btn">ارسال پاسخ</button></div>
        </form>

        <form class="sp-form" data-pane="note" method="POST" action="{{ route('admin.support.internal-note',$ticket) }}" hidden>
          @csrf
          <input type="hidden" name="_tab" value="note">
          <div class="fld"><label for="note_body">یادداشت داخلی تیم</label><textarea id="note_body" name="body" required minlength="2" maxlength="10000" placeholder="نکته، راهکار یا هماهنگی با همکاران...">{{ $composerTab === 'note' ? old('body') : '' }}</textarea>@if($composerTab === 'note')@error('body')<div class="e">{{ $message }}</div>@enderror @endif<div class="h">این متن فقط برای کارشناسان قابل مشاهده است و برای مشتری ارسال یا اعلان نمی‌شود.</div></div>
          <div class="sp-form-foot"><span></span><button type="submit" class="btn sp-warn-btn">ثبت یادداشت داخلی</button></div>
        </form>
      </section>
    </div>

    <aside class="sp-col">
      <section class="sp-card">
        <h3 class="sp-card-title">اطلاعات تیکت</h3>
        <dl class="sp-kv">
          <div><dt>مشتری</dt><dd>{{ $customerName }}@if($ticket->user?->display_name && $ticket->user?->mobile)<small>{{ $ticket->user->mobile }}</small>@endif</dd></div>
          <div><dt>ساختمان مرتبط</dt><dd>{{ $ticket->building?->name ?? 'بدون ساختمان' }}</dd></div>
          <div><dt>دسته‌بندی</dt><dd>{{ $categories[$ticket->category] ?? $ticket->category }}</dd></div>
          <div><dt>مسئول رسیدگی</dt><dd>{{ $ticket->assignedAdmin?->name ?? 'هنوز ارجاع نشده' }}</dd></div>
          <div><dt>حل‌کننده نهایی</dt><dd>{{ $ticket->resolvedByAdmin?->name ?? 'هنوز مشخص نیست' }}</dd></div>
        </dl>
      </section>

      <section class="sp-card">
        <h3 class="sp-card-title">مهلت‌ها (SLA)</h3>
        <dl class="sp-kv">
          <div><dt>مهلت پاسخ اولیه</dt><dd>{{ $ticket->first_response_at ? 'پاسخ داده شده' : ($ticket->first_response_due_at ? $F::date($ticket->first_response_due_at,'j F Y · H:i') : 'ثبت نشده') }}<small>{{ $ticket->first_response_at ? $F::date($ticket->first_response_at,'j F Y · H:i') : ($ticket->first_response_breached_at ? 'مهلت نقض شده' : 'در انتظار اولین پاسخ') }}</small></dd></div>
          <div><dt>مهلت رسیدگی</dt><dd>{{ $ticket->resolution_due_at ? $F::date($ticket->resolution_due_at,'j F Y · H:i') : 'ثبت نشده' }}<small>{{ $finished ? 'پایان یافته' : ($ticket->status==='waiting_customer' ? 'متوقف تا پاسخ مشتری' : ($ticket->resolution_breached_at ? 'مهلت نقض شده' : 'در حال محاسبه')) }}</small></dd></div>
        </dl>
      </section>

      <form class="sp-card sp-side-form" method="POST" action="{{ route('admin.support.update',$ticket) }}">
        @csrf @method('PATCH')
        <h3 class="sp-card-title" style="margin:0">مدیریت وضعیت و اولویت</h3>
        <div class="fld"><label for="assigned_admin_id">ارجاع به کارشناس</label><select id="assigned_admin_id" name="assigned_admin_id"><option value="">بدون مسئول مشخص</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" @selected((int)$ticket->assigned_admin_id===(int)$admin->id)>{{ $admin->name }}</option>@endforeach</select></div>
        <div class="fld"><label for="status">وضعیت</label><select id="status" name="status">@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected($ticket->status===$key)>{{ $label }}</option>@endforeach</select></div>
        <div class="fld"><label for="priority">اولویت</label><select id="priority" name="priority">@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected($ticket->priority===$key)>{{ $label }}</option>@endforeach</select></div>
        <button type="submit" class="btn">ذخیره تغییرات</button>
      </form>

      @if($ticket->priority_suggestion)
        <section class="sp-card sp-ai">
          <h3 class="sp-card-title">تحلیل هوشمند تیکت</h3>
          <div class="sp-actions">
            <span class="sp-pill t-info">پیشنهاد اولویت: {{ $priorities[$ticket->priority_suggestion] ?? $ticket->priority_suggestion }}</span>
            @if($ticket->duplicateOf)
              <a class="sp-pill t-warn" href="{{ route('admin.support.show',$ticket->duplicateOf) }}">درخواست مشابه: {{ $ticket->duplicateOf->number }} · {{ $ticket->duplicate_match_score }}٪</a>
            @endif
          </div>
          @if($ticket->priority_suggestion_reason)<p>{{ $ticket->priority_suggestion_reason }}</p>@endif
          @if($ticket->priority_suggestion !== $ticket->priority)
            <form method="POST" action="{{ route('admin.support.apply-priority-suggestion',$ticket) }}">
              @csrf
              <button type="submit" class="btn sm">اعمال اولویت پیشنهادی</button>
            </form>
          @endif
        </section>
      @endif

      @if($ticket->duplicateTickets->isNotEmpty())
        <section class="sp-card">
          <h3 class="sp-card-title">درخواست‌های مشابه مرتبط</h3>
          <div class="sp-related">
            @foreach($ticket->duplicateTickets as $related)
              <a href="{{ route('admin.support.show',$related) }}">{{ $related->number }} · {{ $related->subject }}</a>
            @endforeach
          </div>
        </section>
      @endif

      @if($ticket->satisfaction_submitted_at)
        <section class="sp-card sp-csat">
          <h3 class="sp-card-title">ارزیابی رضایت مشتری</h3>
          <div class="sp-rating">
            <strong>{{ $ticket->satisfaction_rating }} / ۵</strong>
            <span class="sp-stars" aria-hidden="true" style="font-size:20px">★★★★★<i style="width:{{ min(100, max(0, ((float) $ticket->satisfaction_rating) / 5 * 100)) }}%">★★★★★</i></span>
          </div>
          @if($ticket->satisfaction_comment)<p>{{ $ticket->satisfaction_comment }}</p>@endif
          <span class="sp-sub" style="margin-top:8px">{{ $F::date($ticket->satisfaction_submitted_at,'j F Y · H:i') }}</span>
        </section>
      @endif

      <section class="sp-card">
        <h3 class="sp-card-title">تاریخچه ارجاع و اقدامات</h3>
        @if($ticket->events->isNotEmpty())
          <ul class="sp-timeline">
            @foreach($ticket->events as $event)
              <li class="{{ $event->event_type === 'assigned' ? 't-info' : ($event->event_type === 'status_changed' ? 't-brand' : 't-muted') }}">
                <b>{{ $event->description ?: 'رویداد تیکت' }}</b>
                <small>
                  {{ $event->actorAdmin?->name ?? 'کارشناس حذف‌شده / نامشخص' }} · {{ $F::date($event->created_at,'j F Y · H:i') }}
                  @if($event->event_type==='assigned')
                    · {{ $event->fromAdmin?->name ?? 'بدون مسئول' }} ← {{ $event->toAdmin?->name ?? 'بدون مسئول' }}
                  @endif
                  @if($event->event_type==='status_changed')
                    · {{ $statuses[$event->from_status] ?? $event->from_status }} ← {{ $statuses[$event->to_status] ?? $event->to_status }}
                  @endif
                </small>
              </li>
            @endforeach
          </ul>
        @else
          <p class="sp-help" style="margin:0">برای این تیکت هنوز رویداد تاریخی ثبت نشده است. تاریخچه از زمان استقرار این قابلیت ذخیره می‌شود.</p>
        @endif
      </section>
    </aside>
  </div>
</div>
<script>
(function () {
    var root = document.getElementById('sp-composer');
    if (root) {
        var tabs = root.querySelectorAll('[role="tab"]');
        var panes = root.querySelectorAll('[data-pane]');
        var show = function (name) {
            tabs.forEach(function (t) {
                var on = t.getAttribute('data-tab') === name;
                t.setAttribute('aria-selected', on ? 'true' : 'false');
                t.tabIndex = on ? 0 : -1;
            });
            panes.forEach(function (p) { p.hidden = p.getAttribute('data-pane') !== name; });
        };
        tabs.forEach(function (t) { t.addEventListener('click', function () { show(t.getAttribute('data-tab')); }); });
        show(root.getAttribute('data-initial') || 'reply');
    }
    var canned = document.getElementById('canned_reply');
    if (canned) {
        canned.addEventListener('change', function () {
            if (this.value) {
                var field = document.getElementById('body');
                field.value = this.value;
                field.focus();
            }
        });
    }
})();
</script>
@endsection
