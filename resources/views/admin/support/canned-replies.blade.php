@extends('admin.layout')
@section('title', 'پاسخ‌های آماده پشتیبانی')
@section('content')
<style>
.scr-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}.scr-head h2{font-size:17px;margin:0}.scr-card{border:1px solid var(--line);border-radius:13px;padding:14px;margin-bottom:12px}.scr-form{display:grid;gap:9px}.scr-form input,.scr-form textarea{width:100%;padding:10px;border:1px solid var(--line);border-radius:10px;background:var(--surface);color:var(--text);font:inherit}.scr-form textarea{min-height:100px}.scr-form button,.scr-delete{border:0;border-radius:9px;padding:9px 13px;background:var(--fill-a,#1c4f4a);color:white;font:inherit;font-size:11px;cursor:pointer}.scr-row{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}.scr-row p{white-space:pre-wrap;line-height:1.9;font-size:12px;color:var(--muted)}.scr-delete{background:#b91c1c}
</style>
<div class="scr-head"><h2>مدیریت پاسخ‌های آماده</h2><a href="{{ route('admin.support.index') }}">بازگشت به مرکز پشتیبانی ←</a></div>
<div class="scr-card"><b style="display:block;margin-bottom:10px;font-size:13px">افزودن پاسخ آماده</b>
<form class="scr-form" method="POST" action="{{ route('admin.support.canned-replies.store') }}">
@csrf
<input name="title" required maxlength="120" placeholder="عنوان؛ مثلاً راهنمای تمدید اشتراک" value="{{ old('title') }}">
<textarea name="body" required minlength="5" maxlength="5000" placeholder="متن کامل پاسخ...">{{ old('body') }}</textarea>
<button type="submit">ذخیره پاسخ</button>
</form></div>
@forelse($replies as $reply)
<div class="scr-card"><div class="scr-row"><div style="flex:1;min-width:0"><b>{{ $reply->title }}</b><p>{{ $reply->body }}</p></div>
<form method="POST" action="{{ route('admin.support.canned-replies.destroy',$reply) }}" data-confirm="این پاسخ آماده حذف شود؟">@csrf @method('DELETE')<button class="scr-delete" type="submit">حذف</button></form></div></div>
@empty
<p style="text-align:center;color:var(--muted);padding:24px">هنوز پاسخی ثبت نشده است.</p>
@endforelse
<div>{{ $replies->links() }}</div>
@endsection
