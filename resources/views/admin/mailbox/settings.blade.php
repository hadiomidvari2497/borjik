@extends('admin.layout')

@section('title', 'مدیریت صندوق‌های ایمیل')

@section('content')
<style>
.mail-admin{display:grid;gap:22px}.mail-card{border:1px solid var(--line,#e5e7eb);border-radius:16px;padding:20px}.mail-card h2{font-size:1.05rem;margin:0 0 16px}.mail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.mail-field{display:grid;gap:6px}.mail-field label{font-size:.88rem;font-weight:650}.mail-field input,.mail-field select{width:100%;min-width:0}.mail-table-wrap{overflow:auto}.mail-table{width:100%;border-collapse:collapse;min-width:660px}.mail-table th,.mail-table td{text-align:right;padding:12px;border-bottom:1px solid var(--line,#e5e7eb)}.mail-table th{font-size:.84rem;color:var(--muted,#718096)}.mail-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.mail-hint{font-size:.86rem;color:var(--muted,#718096);line-height:1.9;margin:10px 0 0}.mail-alert{padding:12px;border-radius:10px;background:var(--surface-2,#f8fafc);margin-bottom:14px}@media(max-width:650px){.mail-grid{grid-template-columns:1fr}.mail-card{padding:15px}}
</style>
<div class="mail-admin">
  @if($errors->any())<div class="mail-alert" role="alert">{{ $errors->first() }}</div>@endif
  <section class="mail-card">
    <h2>تنظیم اتصال سرور ایمیل</h2>
    <form method="POST" action="{{ route('admin.mailbox.settings.update') }}">@csrf @method('PUT')
      <div class="mail-grid">
        <div class="mail-field"><label for="imap_host">میزبان IMAP</label><input id="imap_host" class="form-control" name="imap_host" value="{{ old('imap_host',$settings->imap_host) }}" required maxlength="190"></div>
        <div class="mail-field"><label for="imap_port">درگاه IMAP</label><input id="imap_port" class="form-control" type="number" min="1" max="65535" name="imap_port" value="{{ old('imap_port',$settings->imap_port) }}" required></div>
        <div class="mail-field"><label for="smtp_host">میزبان SMTP</label><input id="smtp_host" class="form-control" name="smtp_host" value="{{ old('smtp_host',$settings->smtp_host) }}" required maxlength="190"></div>
        <div class="mail-field"><label for="smtp_port">درگاه SMTP</label><input id="smtp_port" class="form-control" type="number" min="1" max="65535" name="smtp_port" value="{{ old('smtp_port',$settings->smtp_port) }}" required></div>
        <div class="mail-field"><label for="smtp_encryption">رمزنگاری SMTP</label><select id="smtp_encryption" class="form-control" name="smtp_encryption" required><option value="tls" @selected(old('smtp_encryption',$settings->smtp_encryption)==='tls')>STARTTLS (پیشنهادی، 587)</option><option value="ssl" @selected(old('smtp_encryption',$settings->smtp_encryption)==='ssl')>SSL/TLS (معمولاً 465)</option></select></div>
      </div>
      <p class="mail-hint">رمز هر صندوق جداگانه و به‌صورت رمزنگاری‌شده ذخیره می‌شود. این صفحه هیچ رمز عبوری را دوباره نمایش نمی‌دهد.</p>
      <div style="margin-top:16px"><button class="btn btn-primary" type="submit">ذخیره تنظیمات اتصال</button></div>
    </form>
  </section>
  <section class="mail-card">
    <h2>ساخت صندوق ایمیل</h2>
    <form method="POST" action="{{ route('admin.mailbox.accounts.store') }}">@csrf
      <div class="mail-grid">
        <div class="mail-field"><label for="local_part">آدرس ایمیل (نام قبل از @)</label><div style="display:flex;align-items:center;gap:8px"><input id="local_part" class="form-control" name="local_part" placeholder="support" pattern="[a-zA-Z0-9._+-]+" maxlength="64" required><span>@sabkhoneh.ir</span></div></div>
        <div class="mail-field"><label for="display_name">نام نمایشی</label><input id="display_name" class="form-control" name="display_name" maxlength="120" placeholder="پشتیبانی صابخونه"></div>
        <div class="mail-field"><label for="password">رمز صندوق ایمیل</label><input id="password" class="form-control" type="password" name="password" minlength="12" autocomplete="new-password" required></div>
        <div class="mail-field"><label for="admin_user_id">اختصاص به ادمین</label><select id="admin_user_id" class="form-control" name="admin_user_id"><option value="">بدون اختصاص</option>@foreach($admins as $admin)<option value="{{ $admin->id }}">{{ $admin->name ?: $admin->username }} ({{ $admin->username }})</option>@endforeach</select></div>
      </div>
      <p class="mail-hint">رمز باید حداقل ۱۲ نویسه باشد. ساخت صندوق در سرور ایمیل هم انجام می‌شود؛ اگر ابزار امن سرور نصب نشده باشد، حساب در پنل ثبت نخواهد شد.</p>
      <div style="margin-top:16px"><button class="btn btn-primary" type="submit">ساخت صندوق</button></div>
    </form>
  </section>
  <section class="mail-card">
    <h2>صندوق‌های ثبت‌شده</h2>
    <div class="mail-table-wrap"><table class="mail-table"><thead><tr><th>آدرس</th><th>ادمین</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
    @forelse($accounts as $account)
      <tr><td><strong>{{ $account->email }}</strong>@if($account->display_name)<div class="mail-hint">{{ $account->display_name }}</div>@endif</td><td>{{ $account->adminUser?->name ?: $account->adminUser?->username ?: 'بدون اختصاص' }}</td><td>{{ $account->is_active ? 'فعال' : 'غیرفعال' }}</td><td><div class="mail-actions"><form method="POST" action="{{ route('admin.mailbox.accounts.assign',$account) }}">@csrf @method('PUT')<select class="form-control" name="admin_user_id" aria-label="اختصاص صندوق به ادمین"><option value="">بدون اختصاص</option>@foreach($admins as $admin)<option value="{{ $admin->id }}" @selected($account->admin_user_id===$admin->id)>{{ $admin->name ?: $admin->username }}</option>@endforeach</select><button class="btn btn-outline-secondary" style="margin-top:6px" type="submit">ذخیره اختصاص</button></form><a class="btn btn-outline-secondary" href="{{ route('admin.mailbox.index',['account'=>$account->id]) }}">بازکردن صندوق</a><form method="POST" action="{{ route('admin.mailbox.accounts.destroy',$account) }}" data-confirm="حذف صندوق واقعی {{ $account->email }}؟ محتویات آن به پوشهٔ پشتیبان سرور منتقل می‌شود.">@csrf @method('DELETE')<button class="btn btn-outline-danger" type="submit">حذف صندوق</button></form></div></td></tr>
    @empty<tr><td colspan="4" style="padding:24px;text-align:center">هنوز صندوقی ثبت نشده است.</td></tr>@endforelse
    </tbody></table></div>
  </section>
</div>
@endsection
