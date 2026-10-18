@extends('admin.layout')
@section('title', 'اشتراک کاربران')
@section('content')
@php $F = \App\Support\Fa::class; @endphp
<div class="card">
  <div class="page-h">
    <h2>اشتراک کاربران</h2>
    <span class="pill">{{ $F::num($users->total()) }} کاربر</span>
  </div>
  <p style="color:var(--muted);font-size:13px;margin:0 0 14px;line-height:1.8">
    از این بخش می‌توانید برای کاربر موجود، بدون پرداخت آنلاین، یکی از پلن‌ها را دستی فعال کنید. با فعال‌سازی پلن جدید، اشتراک فعال قبلی منقضی می‌شود و ساختمان‌های قبلی کاربر به اشتراک جدید منتقل می‌شوند.
  </p>
  <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="fgrid" style="align-items:end;margin-bottom:16px">
    <div class="fld full">
      <label for="q">جست‌وجوی کاربر با نام، نام شرکت یا شماره موبایل</label>
      <input id="q" name="q" value="{{ $q }}" placeholder="مثلاً 0912... یا نام خانوادگی">
    </div>
    <div class="fld"><button class="btn" type="submit">جست‌وجو</button>
      @if($q !== '') <a class="btn sec" href="{{ route('admin.subscriptions.index') }}">پاک‌کردن</a> @endif
    </div>
  </form>
  @if($users->isEmpty())
    <div class="empty">کاربری با این مشخصات پیدا نشد.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead><tr><th>کاربر</th><th>شماره موبایل</th><th>آخرین اشتراک</th><th>وضعیت</th><th>اعتبار تا</th><th></th></tr></thead>
    <tbody>
    @foreach($users as $user)
      @php
        $sub = $latestSubscriptions->get($user->id);
        $active = $sub && $sub->status === 'active' && $sub->expires_at?->isFuture();
      @endphp
      <tr>
        <td><b>{{ $user->display_name ?: 'بدون نام' }}</b></td>
        <td dir="ltr">{{ $user->mobile }}</td>
        <td>{{ $sub?->plan?->name ?? 'بدون سابقه' }}</td>
        <td>
          @if($active) <span class="pill">فعال</span>
          @elseif($sub) <span class="pill u">{{ $sub->status === 'active' ? 'منقضی‌شده' : 'غیرفعال' }}</span>
          @else <span class="pill u">ندارد</span> @endif
        </td>
        <td>{{ $sub?->expires_at ? $F::date($sub->expires_at) : '—' }}</td>
        <td class="act"><a class="btn sm" href="{{ route('admin.subscriptions.create', ['user_id' => $user->id, 'q' => $q]) }}">فعال‌سازی پلن</a></td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  <div style="margin-top:14px">{{ $users->links() }}</div>
  @endif
</div>
@endsection
