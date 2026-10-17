{{-- تاریخچه‌ی تغییرات یک رکورد؛ فقط برای کسانی که audit.view دارند. استفاده: @include('partials.history', ['subject' => $model]) --}}
@can('audit.view')
@php $entries = \App\Support\AuditPresenter::forModel($subject, 20); @endphp
@if(count($entries))
<div class="card">
  <div class="page-h"><h2>تاریخچه‌ی تغییرات</h2><a class="btn sec sm" href="{{ route('audit.index', ['model' => $subject::class, 'id' => $subject->getKey()]) }}">همه</a></div>
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>زمان</th><th>توسط</th><th>رویداد</th><th>تغییرات</th></tr></thead>
    <tbody>
    @foreach($entries as $e)
      <tr>
        <td style="white-space:nowrap">{{ $e['time'] }}</td>
        <td>{{ $e['actor'] }}</td>
        <td><span class="pill {{ $e['event'] === 'deleted' ? 'u' : ($e['event'] === 'created' ? '' : 'h') }}">{{ $e['event_label'] }}</span></td>
        <td>
          @foreach($e['changes'] as $c)
            <div><b>{{ $c['field'] }}:</b>
              @if($e['event'] === 'updated'){{ $c['old'] }} ← {{ $c['new'] }}@elseif($e['event'] === 'created'){{ $c['new'] }}@else{{ $c['old'] }}@endif
            </div>
          @endforeach
          @if($e['more'] > 0)<div style="color:var(--muted);font-size:12px">و {{ \App\Support\Fa::num($e['more']) }} مورد دیگر</div>@endif
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
</div>
@endif
@endcan
