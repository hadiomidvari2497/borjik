@extends('admin.layout')
@section('title', 'پلن‌های اشتراک')
@section('content')
@php $F = \App\Support\Fa::class; @endphp
<div class="card">
  <div class="page-h">
    <h2>پلن‌های اشتراک</h2>
    <a class="btn" href="{{ route('admin.plans.create') }}">+ پلن جدید</a>
  </div>
  <p style="color:var(--muted);font-size:13px;margin:0 0 12px;line-height:1.7">
    این پلن‌ها در صفحهٔ اشتراک کاربران نمایش داده می‌شوند. برای پلن رایگان، قیمت را <b>۰</b> بگذارید.
  </p>
  @if($plans->isEmpty())
    <div class="empty">هنوز پلنی تعریف نشده است.</div>
  @else
  <div class="tbl-wrap"><table class="tbl">
    <thead>
      <tr>
        <th>نام</th>
        <th>سقف واحد</th>
        <th>مدت (ماه)</th>
        <th>قیمت پایه</th>
        <th>قیمت نهایی</th>
        <th>وضعیت</th>
        <th>تعداد اشتراک</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    @foreach($plans as $plan)
      <tr>
        <td><b>{{ $plan->name }}</b></td>
        <td>{{ $F::num($plan->units_limit) }}</td>
        <td>{{ $F::num($plan->duration_months) }}</td>
        <td>
          @if((int) $plan->price === 0)
            <span class="pill">رایگان</span>
          @else
            {{ $F::money($plan->price) }}
          @endif
        </td>
        <td>
          @if($plan->discountIsActive())
            <del style="color:var(--muted);font-size:12px">{{ $F::money($plan->price) }}</del><br>
            <b>{{ $F::money($plan->effectivePrice()) }}</b> <span class="pill">تخفیف</span>
          @elseif((int) $plan->price === 0)
            <span class="pill">رایگان</span>
          @else
            {{ $F::money($plan->price) }}
          @endif
        </td>
        <td>
          @if($plan->is_active)
            <span class="pill">فعال</span>
          @else
            <span class="pill u">غیرفعال</span>
          @endif
        </td>
        <td>{{ $F::num($plan->subscriptions_count) }}</td>
        <td class="act">
          <a class="btn sec sm" href="{{ route('admin.plans.edit', $plan->id) }}">ویرایش</a>
          <form method="POST" action="{{ route('admin.plans.destroy', $plan->id) }}" data-confirm="این پلن حذف شود؟ اگر قبلاً استفاده شده باشد فقط غیرفعال می‌شود." data-confirm-title="حذف پلن" data-confirm-icon="warning">
            @csrf @method('DELETE')
            <button class="btn dng sm" type="submit">حذف</button>
          </form>
        </td>
      </tr>
    @endforeach
    </tbody>
  </table></div>
  @endif
</div>
@endsection
