@php
  $F = \App\Support\Fa::class;
  $isStaff = $scope === 'staff';
  $title = $isStaff ? 'پرسنل ساختمان' : 'اعضای ساختمان';
  $st = ['active' => ['فعال', ''], 'invited' => ['دعوت‌شده', 'h'], 'inactive' => ['غیرفعال', 'u']];
@endphp
@extends('layouts.app')
@section('title', $title)
@section('content')
<div class="card">
  <div class="page-h"><h2>{{ $title }}</h2></div>
  <form method="POST" action="{{ route('members.invite') }}" style="margin-top:10px">@csrf
    <div class="fgrid" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));align-items:end">
      <div class="fld"><label for="mobile">شماره‌ی موبایل *</label><input id="mobile" name="mobile" inputmode="tel" placeholder="۰۹۱۲۳۴۵۶۷۸۹" value="{{ old('mobile') }}" required>@error('mobile')<div class="e">{{ $message }}</div>@enderror</div>
      <div class="fld"><label for="role">نقش *</label>
        <select id="role" name="role">@foreach($invitable as $k)<option value="{{ $k }}">{{ $roleNames[$k] ?? $k }}</option>@endforeach</select></div>
      <div class="fld"><button class="btn" type="submit" style="width:100%;justify-content:center">ارسال دعوت‌نامه با پیامک</button></div>
    </div>
  </form>
</div>

<div class="card">
  @if($people->isEmpty())<div class="empty">هنوز کسی ثبت نشده است.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>نام</th><th>موبایل</th><th>نقش</th>@unless($isStaff)<th>واحدها</th>@endunless<th>وضعیت</th><th></th></tr></thead>
    <tbody>
    @foreach($people as $p)
      <tr>
        <td><b>{{ $p['name'] ?: '—' }}</b> @if($p['is_self'])<span class="pill">شما</span>@endif</td>
        <td dir="ltr" style="text-align:end">{{ $p['mobile'] }}</td>
        <td>@foreach($p['roles'] as $label)<span class="pill" style="margin-inline-end:4px">{{ $label }}</span>@endforeach</td>
        @unless($isStaff)<td style="font-size:12.5px">@forelse($units[$p['user_id']] ?? [] as $l){{ $l }}<br>@empty<span style="color:var(--muted)">—</span>@endforelse</td>@endunless
        <td><span class="pill {{ $st[$p['status']][1] }}">{{ $st[$p['status']][0] }}</span></td>
        <td class="act">
          @unless($p['locked'])
            @if($p['status'] === 'active')
              <form method="POST" action="{{ route('team.deactivate', $p['user_id']) }}" data-confirm="این شخص غیرفعال شود؟ دسترسی‌اش به ساختمان قطع می‌شود ولی اطلاعاتش حفظ می‌ماند." data-confirm-title="غیرفعال‌سازی" data-confirm-icon="warning">@csrf<input type="hidden" name="scope" value="{{ $scope }}"><button class="btn dng sm" type="submit">غیرفعال‌سازی</button></form>
            @elseif($p['status'] === 'inactive')
              <form method="POST" action="{{ route('team.activate', $p['user_id']) }}">@csrf<input type="hidden" name="scope" value="{{ $scope }}"><button class="btn sec sm" type="submit">فعال‌سازی</button></form>
            @else
              <form method="POST" action="{{ route('members.invite') }}">@csrf<input type="hidden" name="mobile" value="{{ $p['mobile'] }}"><input type="hidden" name="role" value="{{ array_key_first($p['roles']) }}"><button class="btn sec sm" type="submit">ارسال دوباره</button></form>
              <form method="POST" action="{{ route('team.invitation.cancel', $p['user_id']) }}" data-confirm="دعوت لغو شود؟" data-confirm-title="لغو دعوت" data-confirm-icon="warning">@csrf @method('DELETE')<input type="hidden" name="scope" value="{{ $scope }}"><button class="btn dng sm" type="submit">لغو دعوت</button></form>
            @endif
            @if($isStaff)
              <details style="position:relative"><summary class="btn sec sm" style="list-style:none;cursor:pointer">نقش‌ها</summary>
                <form method="POST" action="{{ route('staff.roles.update', $p['user_id']) }}" style="position:absolute;inset-inline-end:0;z-index:4;background:var(--card);border:1px solid var(--line);border-radius:14px;padding:12px;min-width:190px;box-shadow:var(--shadow)">@csrf @method('PUT')
                  @foreach($editable as $k)<label style="display:flex;gap:8px;align-items:center;font-size:13px;margin-bottom:6px"><input type="checkbox" name="roles[]" value="{{ $k }}" style="width:auto" @checked(array_key_exists($k, $p['roles']))>{{ $roleNames[$k] ?? $k }}</label>@endforeach
                  <button class="btn sm" type="submit" style="width:100%;justify-content:center;margin-top:6px">ذخیره</button>
                </form></details>
            @endif
          @endunless
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</div>
@endsection
