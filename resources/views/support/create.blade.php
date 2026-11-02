@extends('layouts.app')
@section('title', 'ثبت درخواست پشتیبانی')
@section('content')
<style>
.support-form{max-width:760px;margin:auto}.support-form-intro{margin-bottom:20px}.support-form-intro h2{font-size:19px;font-weight:900;margin:0 0 6px}.support-form-intro p{font-size:12px;color:var(--muted);line-height:1.9;margin:0}.support-field{margin-bottom:16px}.support-field label{display:block;font-size:12px;font-weight:800;margin-bottom:7px}.support-field input,.support-field select,.support-field textarea{width:100%;border:1px solid var(--gb);border-radius:12px;padding:11px 13px;background:var(--panel);color:var(--ink);font:inherit;font-size:12px}.support-field textarea{min-height:180px;resize:vertical;line-height:2}.support-help{font-size:10px;color:var(--muted);margin-top:6px}.support-actions{display:flex;gap:9px;flex-wrap:wrap}.support-primary{border:0;border-radius:11px;padding:11px 18px;background:var(--brand,#1f766b);color:#fff;font:inherit;font-size:12px;font-weight:850;cursor:pointer}.support-secondary{border:1px solid var(--gb);border-radius:11px;padding:10px 16px;color:var(--ink);text-decoration:none;font-size:12px}
</style>
<form class="support-form" method="POST" action="{{ route('support.store') }}">
  @csrf
  <div class="support-form-intro"><h2>درخواستت رو برای ما بنویس</h2><p>برای اینکه سریع‌تر راهنمایی‌ات کنیم، موضوع و توضیحات رو تا حد ممکن دقیق وارد کن.</p></div>
  <div class="support-field"><label for="subject">موضوع درخواست</label><input id="subject" name="subject" value="{{ old('subject') }}" required minlength="5" maxlength="180" placeholder="مثلاً مشکل در تمدید اشتراک">@error('subject')<div class="support-help" style="color:#dc4444">{{ $message }}</div>@enderror</div>
  <div class="support-field"><label for="category">دسته‌بندی</label><select id="category" name="category" required>@foreach($categories as $key=>$label)<option value="{{ $key }}" @selected(old('category','general')===$key)>{{ $label }}</option>@endforeach</select>@error('category')<div class="support-help" style="color:#dc4444">{{ $message }}</div>@enderror</div>
  <div class="support-field"><label for="body">شرح درخواست</label><textarea id="body" name="body" required minlength="15" maxlength="10000" placeholder="مشکل یا سؤال رو با جزئیات بنویس...">{{ old('body') }}</textarea><div class="support-help">حداقل ۱۵ نویسه · اطلاعات ورود و رمز عبورت رو اینجا ننویس.</div>@error('body')<div class="support-help" style="color:#dc4444">{{ $message }}</div>@enderror</div>
  <div class="support-actions"><button class="support-primary" type="submit">ثبت درخواست پشتیبانی</button><a class="support-secondary" href="{{ route('support.index') }}">انصراف</a></div>
</form>
@endsection
