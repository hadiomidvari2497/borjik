@extends('admin.layout')
@section('title', 'اتصال هوش مصنوعی')
@section('content')
@php
  $labels = ['read:overview' => 'نمای کلی سامانه', 'read:diagnostics' => 'گزارش رخدادهای فنی', 'actions:request' => 'درخواست عملیات نیازمند تأیید'];
  $actionLabels = ['run_health_check' => 'اجرای بررسی سلامت', 'run_backup' => 'اجرای بکاپ'];
  $statusLabels = ['pending' => 'در انتظار تأیید', 'running' => 'در حال اجرا', 'success' => 'موفق', 'failed' => 'ناموفق', 'rejected' => 'رد شده'];
@endphp
<style>
.ai-wrap{display:grid;gap:18px}.ai-head{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap}.ai-muted{font-size:12px;color:var(--muted);line-height:1.9}.ai-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.ai-card{border:1px solid var(--line);border-radius:14px;padding:16px;min-width:0}.ai-form{display:grid;grid-template-columns:1fr 1fr;gap:12px}.ai-form label{display:block;color:var(--muted);font-size:12px;margin-bottom:6px}.ai-form input[type=text],.ai-form input[type=number]{width:100%;min-width:0}.ai-checks{display:grid;gap:8px}.ai-check{display:flex;align-items:center;gap:8px;font-size:13px}.ai-check input{width:auto}.ai-table-wrap{overflow:auto}.ai-table{width:100%;border-collapse:collapse;min-width:650px}.ai-table th,.ai-table td{padding:10px;border-bottom:1px solid var(--line);text-align:right;vertical-align:top}.ai-table th{font-size:12px;color:var(--muted)}.ai-pill{display:inline-flex;border-radius:999px;padding:4px 9px;font-size:11px;font-weight:800;background:var(--surface);border:1px solid var(--line)}.ai-secret{padding:12px;border:1px solid #a7d8b7;border-radius:12px;background:#edf9f0;color:#175b2e;overflow-wrap:anywhere}.ai-actions{display:flex;gap:7px;flex-wrap:wrap}.ai-btn{border:1px solid var(--line);border-radius:9px;padding:8px 11px;background:var(--surface);color:inherit;cursor:pointer;font:inherit}.ai-btn-danger{border-color:#d99b96;color:#a52b24}.ai-token{font-family:monospace;font-size:13px}.ai-warn{border:1px solid #e4c98c;background:#fff8e7;color:#77530b;padding:12px;border-radius:10px;font-size:12px;line-height:1.9}
@media(max-width:760px){.ai-grid,.ai-form{grid-template-columns:1fr}}
:root[data-theme="dark"] .ai-secret{background:rgba(47,160,96,.16);color:#a7e7b8;border-color:rgba(80,190,120,.45)}:root[data-theme="dark"] .ai-warn{background:rgba(232,163,61,.12);color:#f4d58d}
</style>
<div class="ai-wrap">
  <div class="ai-head"><div><h2 style="margin:0">اتصال امن هوش مصنوعی</h2><p class="ai-muted">توکن‌های محدود، API نسخه‌بندی‌شده و صف عملیات با تأیید دستی مدیر.</p></div><span class="ai-pill">Bearer Token · API v1</span></div>
  <div class="ai-warn">توکن فقط هنگام ساخت نمایش داده می‌شود. آن را در چت، کد فرانت‌اند یا مخزن Git قرار نده؛ فقط در Secret Store ابزار واسط و روی اتصال HTTPS استفاده کن. درخواست عملیات هیچ‌وقت خودکار اجرا نمی‌شود.</div>

  @if(session('issued_token'))
    <section class="ai-secret" role="alert"><strong>توکن جدید — فقط همین بار نمایش داده می‌شود:</strong><p class="ai-token" id="issued-ai-token">{{ session('issued_token') }}</p><button class="ai-btn" type="button" onclick="navigator.clipboard.writeText(document.getElementById('issued-ai-token').textContent)">کپی توکن</button><p>بعد از ترک این صفحه، مقدار کامل قابل بازیابی نیست.</p></section>
  @endif

  <div class="ai-grid">
    <section class="ai-card">
      <h3 style="margin-top:0">ساخت توکن جدید</h3>
      <form method="POST" action="{{ route('admin.ai-connector.tokens.store') }}">@csrf
        <div class="ai-form">
          <div><label for="ai-name">نام اتصال</label><input id="ai-name" type="text" name="name" maxlength="100" required value="{{ old('name', 'ChatGPT connector') }}" placeholder="مثلاً اتصال هوش مصنوعی"></div>
          <div><label for="ai-expiry">اعتبار (روز)</label><input id="ai-expiry" type="number" name="expires_in_days" min="1" max="365" required value="{{ old('expires_in_days', 90) }}"></div>
        </div>
        <p class="ai-muted">دسترسی‌های این توکن:</p>
        <div class="ai-checks">
          @foreach($abilities as $ability)
            <label class="ai-check"><input type="checkbox" name="abilities[]" value="{{ $ability }}" @checked(in_array($ability, old('abilities', ['read:overview', 'read:diagnostics']), true))> {{ $labels[$ability] }}</label>
          @endforeach
        </div>
        @error('abilities')<p class="ai-muted" style="color:#b42318">{{ $message }}</p>@enderror
        <div style="margin-top:14px"><button class="ai-btn" type="submit">ساخت توکن امن</button></div>
      </form>
    </section>
    <section class="ai-card">
      <h3 style="margin-top:0">مسیرهای API</h3>
      <p class="ai-muted"><strong>GET /api/ai/v1/overview</strong><br>آمار کلی و آخرین وضعیت سلامت/بکاپ؛ نیازمند read:overview.</p>
      <p class="ai-muted"><strong>GET /api/ai/v1/incidents?limit=10</strong><br>رخدادهای فنی بدون متن خطا، مسیر فایل، IP یا شناسه اشخاص؛ نیازمند read:diagnostics.</p>
      <p class="ai-muted"><strong>POST /api/ai/v1/actions</strong><br>درخواست run_health_check یا run_backup با reason؛ نیازمند actions:request. عملیات تا تأیید مدیر اجرا نمی‌شود.</p>
      <p class="ai-muted">احراز هویت با هدر <code>Authorization: Bearer …</code>، محدودیت نرخ و تاریخ انقضا انجام می‌شود.</p>
    </section>
  </div>

  <section class="ai-card">
    <div class="ai-head"><h3 style="margin:0">توکن‌های صادرشده</h3><span class="ai-muted">مقدار کامل هیچ‌وقت در دیتابیس ذخیره نمی‌شود.</span></div>
    <div class="ai-table-wrap"><table class="ai-table"><thead><tr><th>نام</th><th>پیشوند</th><th>دسترسی</th><th>وضعیت</th><th>آخرین استفاده</th><th>اقدام</th></tr></thead><tbody>
    @forelse($tokens as $token)
      <tr><td>{{ $token->name }}</td><td><code>{{ $token->token_prefix }}…</code></td><td>{{ collect($token->abilities)->map(fn($a) => $labels[$a] ?? $a)->join('، ') }}</td><td><span class="ai-pill">{{ $token->revoked_at ? 'باطل‌شده' : (($token->expires_at && $token->expires_at->isPast()) ? 'منقضی' : 'فعال') }}</span><br><span class="ai-muted">انقضا: {{ $token->expires_at?->format('Y-m-d H:i') ?? 'بدون انقضا' }}</span></td><td>{{ $token->last_used_at?->format('Y-m-d H:i:s') ?? 'هنوز استفاده نشده' }}</td><td>@if(!$token->revoked_at)<form method="POST" action="{{ route('admin.ai-connector.tokens.revoke', $token) }}" onsubmit="return confirm('این توکن فوراً باطل شود؟')">@csrf @method('DELETE')<button class="ai-btn ai-btn-danger" type="submit">ابطال</button></form>@else — @endif</td></tr>
    @empty<tr><td colspan="6">هنوز توکنی صادر نشده است.</td></tr>@endforelse
    </tbody></table></div>
    {{ $tokens->links() }}
  </section>

  <section class="ai-card">
    <div class="ai-head"><h3 style="margin:0">صف عملیات درخواستی</h3><span class="ai-muted">هر اجرا به اقدام صریح مدیر نیاز دارد.</span></div>
    <div class="ai-table-wrap"><table class="ai-table"><thead><tr><th>درخواست</th><th>دلیل</th><th>وضعیت</th><th>زمان</th><th>نتیجه/اقدام مدیر</th></tr></thead><tbody>
    @forelse($actions as $action)
      <tr><td>#{{ $action->id }} · {{ $actionLabels[$action->action] ?? $action->action }}</td><td>{{ $action->reason }}</td><td><span class="ai-pill">{{ $statusLabels[$action->status] ?? $action->status }}</span></td><td>{{ $action->created_at?->format('Y-m-d H:i') }}</td><td>
        @if($action->status === 'pending')
          <div class="ai-actions">
            <form method="POST" action="{{ route('admin.ai-connector.actions.approve', $action) }}">@csrf<label class="ai-check"><input type="checkbox" name="confirm_execution" value="1" required> اجرای این عملیات را تأیید می‌کنم</label><button class="ai-btn" type="submit">تأیید و اجرا</button></form>
            <form method="POST" action="{{ route('admin.ai-connector.actions.reject', $action) }}">@csrf @method('DELETE')<button class="ai-btn ai-btn-danger" type="submit">رد درخواست</button></form>
          </div>
        @elseif($action->result)
          <details><summary>مشاهده نتیجه</summary><pre style="white-space:pre-wrap;overflow-wrap:anywhere">{{ json_encode($action->result, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE) }}</pre></details>
        @else — @endif
      </td></tr>
    @empty<tr><td colspan="5">درخواستی در صف نیست.</td></tr>@endforelse
    </tbody></table></div>
    {{ $actions->links() }}
  </section>
</div>
@endsection
