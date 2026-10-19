@extends('layouts.app')
@section('title', 'تیکت و تسک')
@section('content')
@php
  $statusClasses = ['new'=>'#4c83d6','review'=>'#b78328','doing'=>'#21877c','waiting'=>'#9275bd','done'=>'#258a62','closed'=>'#748094'];
  $priorityClasses = ['urgent'=>'#d94c42','high'=>'#d97732','medium'=>'#b78328','low'=>'#748094'];
@endphp
<style>
.task-page{display:grid;gap:18px}.task-hero{padding:24px;border-radius:22px;background:linear-gradient(115deg,#174d49,#217f74 60%,#35a99a);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:18px}.task-hero h2{font-size:22px;margin:0 0 8px}.task-hero p{font-size:12px;line-height:1.9;opacity:.88;max-width:650px}.task-action{display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;background:#fff;color:#17645c;border-radius:12px;padding:11px 15px;font-size:12px;font-weight:800;text-decoration:none}.task-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}.task-stat{padding:15px 17px;border:1px solid var(--line);border-radius:15px;background:var(--card)}.task-stat b{display:block;font-size:23px}.task-stat span{display:block;color:var(--muted);font-size:11px;margin-top:3px}.task-filters{display:grid;grid-template-columns:minmax(170px,2fr) repeat(5,minmax(110px,1fr)) auto;gap:10px;align-items:end;padding:16px 18px;border-bottom:1px solid var(--line)}.task-filters label{display:block;font-size:11px;font-weight:700;color:var(--muted);margin-bottom:6px}.task-filters input,.task-filters select{width:100%;min-width:0;border:1px solid var(--line);border-radius:10px;background:var(--card);color:var(--text);padding:10px;font:inherit;font-size:12px}.task-filter-actions{display:flex;gap:7px}.task-filter-actions .btn{white-space:nowrap}.task-clear{display:inline-flex;align-items:center;padding:9px 11px;border:1px solid var(--line);border-radius:10px;text-decoration:none;color:var(--muted);font-size:12px;white-space:nowrap}.task-switch{display:flex;gap:6px}.task-switch a{padding:8px 12px;border-radius:9px;border:1px solid var(--line);color:var(--muted);font-size:11px;text-decoration:none}.task-switch a.active{background:var(--brand-soft);border-color:var(--brand);color:var(--brand-d);font-weight:800}.task-table-wrap{overflow:auto}.task-table{width:100%;border-collapse:collapse;min-width:760px}.task-table th,.task-table td{padding:13px 16px;text-align:right;border-bottom:1px solid var(--line);font-size:12px;vertical-align:middle}.task-table th{font-size:10px;color:var(--muted);background:color-mix(in srgb,var(--track) 35%,transparent)}.task-title{font-weight:800;color:var(--text);text-decoration:none}.task-title:hover{color:var(--brand-d)}.task-meta{display:flex;gap:7px;flex-wrap:wrap;color:var(--muted);font-size:10px;margin-top:5px}.task-pill{display:inline-flex;align-items:center;padding:5px 8px;border-radius:8px;font-size:10px;font-weight:800;white-space:nowrap}.task-assignees{display:flex;gap:4px;flex-wrap:wrap}.task-assignees span{font-size:10px;padding:4px 6px;border-radius:6px;background:var(--track)}.task-overdue{color:#d94c42;background:rgba(224,69,58,.12);padding:3px 6px;border-radius:6px;font-size:10px;font-weight:800}.task-empty{padding:28px;text-align:center;color:var(--muted);font-size:12px}.kanban{display:grid;grid-template-columns:repeat(6,minmax(230px,1fr));gap:10px;overflow-x:auto;padding:14px}.kanban-col{min-height:250px;background:color-mix(in srgb,var(--track) 28%,transparent);border:1px solid var(--line);border-radius:14px;padding:10px}.kanban-col.drag-over{outline:2px dashed var(--brand);outline-offset:-3px;background:var(--brand-soft)}.kanban-head{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:3px 2px 10px;font-size:11px;font-weight:800}.kanban-count{border-radius:7px;padding:3px 6px;background:var(--card);color:var(--muted)}.kanban-stack{display:grid;gap:9px;min-height:200px}.kanban-card{padding:12px;border:1px solid var(--line);border-radius:12px;background:var(--card);box-shadow:0 3px 8px rgba(0,0,0,.03);cursor:grab}.kanban-card:active{cursor:grabbing}.kanban-card a{font-size:12px;font-weight:800;color:var(--text);text-decoration:none;line-height:1.8}.kanban-card p{font-size:10px;color:var(--muted);line-height:1.8;margin:6px 0}.kanban-card-foot{display:flex;justify-content:space-between;gap:6px;flex-wrap:wrap;margin-top:9px}.kanban-card-foot span{font-size:9px;padding:4px 6px;border-radius:6px;background:var(--track)}.kanban-card[aria-busy=true]{opacity:.5}.task-flash{font-size:11px;color:var(--muted);padding:0 18px 12px}@media(max-width:900px){.task-filters{grid-template-columns:repeat(2,minmax(0,1fr))}.task-filter-actions{grid-column:1/-1}.task-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.task-hero{padding:18px;align-items:flex-start;flex-direction:column}.task-hero h2{font-size:19px}.task-stats{gap:8px}.task-stat{padding:12px}.task-stat b{font-size:20px}.task-filters{grid-template-columns:1fr;padding:13px}.task-filter-actions{grid-column:auto}.task-filter-actions .btn,.task-clear{flex:1;text-align:center}.task-switch{width:100%}.task-switch a{flex:1;text-align:center}}
</style>
<div class="task-page">
  <section class="task-hero"><div><h2>مدیریت تیکت و تسک</h2><p>کارهای ساختمان را ارجاع بده، مسئولیت و مهلت را پیگیری کن و تمام تغییرات و گفت‌وگوها را در یک مسیر ثبت‌شده ببین.</p></div>@if($canManage)<a class="task-action" href="{{ route('tickets.create') }}">＋ ایجاد تسک جدید</a>@endif</section>
  <section class="task-stats">
    <div class="task-stat"><b>{{ \App\Support\Fa::num($stats['total']) }}</b><span>کل تسک‌ها</span></div>
    <div class="task-stat"><b>{{ \App\Support\Fa::num($stats['open']) }}</b><span>در جریان</span></div>
    <div class="task-stat"><b>{{ \App\Support\Fa::num($stats['overdue']) }}</b><span>عقب‌افتاده</span></div>
    <div class="task-stat"><b>{{ \App\Support\Fa::num($stats['done']) }}</b><span>انجام‌شده یا بسته</span></div>
  </section>
  <section class="card" style="padding:0;overflow:hidden">
    <form class="task-filters" method="GET" action="{{ route('tickets.index') }}">
      <div><label for="task-q">جست‌وجو</label><input id="task-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="عنوان، شماره، شرح یا محل"></div>
      <div><label for="task-status">وضعیت</label><select id="task-status" name="status"><option value="">همه وضعیت‌ها</option>@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected(($filters['status'] ?? '')===$key)>{{ $label }}</option>@endforeach</select></div>
      <div><label for="task-priority">اولویت</label><select id="task-priority" name="priority"><option value="">همه اولویت‌ها</option>@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected(($filters['priority'] ?? '')===$key)>{{ $label }}</option>@endforeach</select></div>
      <div><label for="task-category">دسته‌بندی</label><select id="task-category" name="category"><option value="">همه دسته‌ها</option>@foreach($categories as $key=>$label)<option value="{{ $key }}" @selected(($filters['category'] ?? '')===$key)>{{ $label }}</option>@endforeach</select></div>
      <div><label for="task-assignee">مسئول</label><select id="task-assignee" name="assignee"><option value="">همه مسئولان</option>@foreach($staff as $person)<option value="{{ $person->id }}" @selected((string)($filters['assignee'] ?? '')===(string)$person->id)>{{ $person->display_name ?: $person->mobile }}</option>@endforeach</select></div>
      <div><label for="task-overdue">مهلت</label><select id="task-overdue" name="overdue"><option value="">همه تسک‌ها</option><option value="1" @selected(($filters['overdue'] ?? '')==='1')>فقط عقب‌افتاده</option></select></div>
      <input type="hidden" name="view" value="{{ $kanban ? 'kanban' : 'list' }}">
      <div class="task-filter-actions"><button class="btn" type="submit">اعمال فیلتر</button><a class="task-clear" href="{{ route('tickets.index', ['view'=>$kanban?'kanban':'list']) }}">پاک‌کردن</a></div>
    </form>
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding:12px 18px;border-bottom:1px solid var(--line)">
      <span style="font-size:11px;color:var(--muted)">{{ \App\Support\Fa::num($tickets->count()) }} تسک در نتایج</span>
      <div class="task-switch"><a class="{{ $kanban?'active':'' }}" href="{{ route('tickets.index', array_merge(request()->query(),['view'=>'kanban'])) }}">نمای کانبان</a><a class="{{ !$kanban?'active':'' }}" href="{{ route('tickets.index', array_merge(request()->query(),['view'=>'list'])) }}">نمای فهرست</a></div>
    </div>
    @if($kanban)
      <div class="kanban" id="task-kanban" data-can-move="{{ (auth()->user()->hasPermission('ticket.view.all') || auth()->user()->hasPermission('ticket.respond')) ? '1' : '0' }}">
        @foreach($statuses as $key=>$label)
          @php $columnTickets=$tickets->filter(fn($t)=>$t->status->value===$key); @endphp
          <section class="kanban-col" data-status="{{ $key }}">
            <div class="kanban-head"><span>{{ $label }}</span><span class="kanban-count" data-count="{{ $columnTickets->count() }}">{{ \App\Support\Fa::num($columnTickets->count()) }}</span></div>
            <div class="kanban-stack">
              @foreach($columnTickets as $ticket)
                @php $status=$ticket->status->value; $priority=$ticket->priority->value; @endphp
                <article class="kanban-card" draggable="{{ (auth()->user()->hasPermission('ticket.view.all') || auth()->user()->hasPermission('ticket.respond')) ? 'true':'false' }}" data-id="{{ $ticket->id }}" data-status-url="{{ route('tickets.status',$ticket->id) }}" data-current-status="{{ $status }}">
                  <a href="{{ route('tickets.show',$ticket->id) }}">{{ $ticket->title }}</a>
                  <p>{{ \Illuminate\Support\Str::limit($ticket->description,100) }}</p>
                  <div class="kanban-card-foot"><span style="color:{{ $priorityClasses[$priority] }}">{{ $priorities[$priority] }}</span><span>{{ $ticket->number }}</span></div>
                  <div class="kanban-card-foot"><span>{{ $ticket->deadline ? \App\Support\Fa::date($ticket->deadline, 'Y/m/d') : 'بدون مهلت' }}</span>@if($ticket->deadline && $ticket->deadline->isPast() && !in_array($status,['done','closed'],true))<span class="task-overdue">عقب‌افتاده</span>@endif</div>
                </article>
              @endforeach
            </div>
          </section>
        @endforeach
      </div>
      <p class="task-flash" id="task-kanban-message" aria-live="polite">برای تغییر وضعیت، کارت را به ستون موردنظر بکشید. تغییرات پس از تأیید سرور ثبت می‌شود.</p>
    @else
      <div class="task-table-wrap"><table class="task-table"><thead><tr><th>تسک</th><th>وضعیت</th><th>اولویت</th><th>مسئولان</th><th>مهلت</th></tr></thead><tbody>
      @forelse($tickets as $ticket)
        @php $status=$ticket->status->value; $priority=$ticket->priority->value; @endphp
        <tr><td><a class="task-title" href="{{ route('tickets.show',$ticket->id) }}">{{ $ticket->title }}</a><div class="task-meta"><span>{{ $ticket->number }}</span><span>·</span><span>{{ $ticket->created_at ? \App\Support\Fa::date($ticket->created_at, 'Y/m/d') : '—' }}</span></div></td><td><span class="task-pill" style="color:{{ $statusClasses[$status] }};background:color-mix(in srgb,{{ $statusClasses[$status] }} 12%,transparent)">{{ $statuses[$status] }}</span></td><td><span class="task-pill" style="color:{{ $priorityClasses[$priority] }};background:color-mix(in srgb,{{ $priorityClasses[$priority] }} 12%,transparent)">{{ $priorities[$priority] }}</span></td><td><div class="task-assignees">@forelse($ticket->assignees as $assignee)<span>{{ $assignee->user?->display_name ?: $assignee->user?->mobile }}</span>@empty<span>بدون مسئول</span>@endforelse</div></td><td>@if($ticket->deadline){{ \App\Support\Fa::date($ticket->deadline, 'Y/m/d H:i') }} @if($ticket->deadline->isPast() && !in_array($status,['done','closed'],true))<span class="task-overdue">عقب‌افتاده</span>@endif @else—@endif</td></tr>
      @empty<tr><td colspan="5"><div class="task-empty">تسکی با این مشخصات پیدا نشد.</div></td></tr>@endforelse
      </tbody></table></div>
      <div style="padding:14px 18px;border-top:1px solid var(--line)">{{ $tickets->links() }}</div>
    @endif
  </section>
</div>
@if($kanban)
<script>
(() => {
  let board = document.getElementById('task-kanban');
  const message = document.getElementById('task-kanban-message');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value;
  const faNumber = value => Number(value).toLocaleString('fa-IR');
  let dragged = null;
  let refreshing = false;

  const bindBoard = currentBoard => {
    if (!currentBoard || currentBoard.dataset.bound === '1') return;
    currentBoard.dataset.bound = '1';

    currentBoard.querySelectorAll('.kanban-card[draggable="true"]').forEach(card => {
      card.addEventListener('dragstart', event => {
        dragged = card;
        card.setAttribute('aria-busy', 'true');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.id);
      });
      card.addEventListener('dragend', () => {
        card.removeAttribute('aria-busy');
        currentBoard.querySelectorAll('.kanban-col').forEach(col => col.classList.remove('drag-over'));
        dragged = null;
      });
    });

    currentBoard.querySelectorAll('.kanban-col').forEach(col => {
      col.addEventListener('dragover', event => {
        event.preventDefault();
        col.classList.add('drag-over');
      });
      col.addEventListener('dragleave', event => {
        if (!col.contains(event.relatedTarget)) col.classList.remove('drag-over');
      });
      col.addEventListener('drop', async event => {
        event.preventDefault();
        col.classList.remove('drag-over');
        if (!dragged || !csrf || dragged.dataset.busy === '1') return;
        const nextStatus = col.dataset.status;
        if (nextStatus === dragged.dataset.currentStatus) return;

        const card = dragged;
        const oldColumn = card.closest('.kanban-col');
        const oldCount = oldColumn.querySelector('.kanban-count');
        const newCount = col.querySelector('.kanban-count');
        card.dataset.busy = '1';
        if (message) message.textContent = 'در حال ثبت وضعیت...';

        try {
          const response = await fetch(card.dataset.statusUrl, {
            method: 'POST',
            headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({_token:csrf,status:nextStatus})
          });
          const payload = await response.json().catch(() => ({}));
          if (!response.ok || payload.ok !== true) {
            throw new Error(payload.message || (response.status === 422
              ? 'این جابه‌جایی با روند مجاز تغییر وضعیت تسک سازگار نیست.'
              : 'ثبت وضعیت انجام نشد.'));
          }

          col.querySelector('.kanban-stack').appendChild(card);
          card.dataset.currentStatus = payload.status || nextStatus;
          oldCount.dataset.count = String(Math.max(0,Number(oldCount.dataset.count || 0)-1));
          newCount.dataset.count = String(Number(newCount.dataset.count || 0)+1);
          oldCount.textContent = faNumber(oldCount.dataset.count);
          newCount.textContent = faNumber(newCount.dataset.count);
          if (message) message.textContent = 'وضعیت با موفقیت ثبت شد.';
        } catch (error) {
          if (message) message.textContent = error.message || 'تغییر وضعیت ثبت نشد.';
        } finally {
          delete card.dataset.busy;
          card.removeAttribute('aria-busy');
        }
      });
    });
  };

  bindBoard(board);

  // Keep the board synchronized with changes made by other users without reloading the page.
  window.setInterval(async () => {
    if (!board || document.hidden || refreshing || dragged ||
        board.querySelector('.kanban-card[data-busy="1"], .kanban-card[aria-busy="true"]')) return;

    refreshing = true;
    try {
      const response = await fetch(window.location.href, {
        method: 'GET',
        headers: {'Accept':'text/html','X-Requested-With':'XMLHttpRequest'},
        cache: 'no-store'
      });
      if (!response.ok) return;
      const html = await response.text();
      const parsed = new DOMParser().parseFromString(html, 'text/html');
      const freshBoard = parsed.getElementById('task-kanban');
      if (!freshBoard || !board.isConnected) return;

      // Avoid replacing the board when its rendered state has not changed.
      if (freshBoard.innerHTML !== board.innerHTML) {
        const replacement = document.importNode(freshBoard, true);
        board.replaceWith(replacement);
        board = replacement;
        bindBoard(board);
      }
    } catch (_) {
      // Temporary network failures should not interrupt local drag-and-drop interactions.
    } finally {
      refreshing = false;
    }
  }, 15000);
})();
</script>
@endif
@endsection
