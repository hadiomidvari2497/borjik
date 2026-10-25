@extends('admin.layout')

@section('title', 'ارسال ایمیل')

@section('content')
<style>.compose-card{max-width:900px;border:1px solid var(--line,#e5e7eb);border-radius:16px;padding:22px}.compose-grid{display:grid;gap:14px}.compose-grid label{display:grid;gap:6px;font-weight:650}.compose-grid textarea{min-height:230px;line-height:1.9}.compose-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}</style>
<div class="compose-card">
  <p>ارسال از صندوق <strong>{{ $account->email }}</strong></p>
  @if($errors->any())<div role="alert" class="alert alert-danger">{{ $errors->first() }}</div>@endif
  <form method="POST" action="{{ route('admin.mailbox.send') }}">@csrf
    <div class="compose-grid">
      <label>گیرنده<input class="form-control" type="email" name="to" value="{{ old('to') }}" maxlength="254" required></label>
      <label>موضوع<input class="form-control" name="subject" value="{{ old('subject') }}" maxlength="200" required></label>
      <label>متن پیام<textarea class="form-control" name="body" maxlength="50000" required>{{ old('body') }}</textarea></label>
    </div>
    <div class="compose-actions"><button class="btn btn-primary" type="submit">ارسال ایمیل</button><a class="btn btn-outline-secondary" href="{{ route('admin.mailbox.index') }}">انصراف</a></div>
  </form>
</div>
@endsection
