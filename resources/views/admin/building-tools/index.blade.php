@extends('admin.layout')

@section('title', 'مدیریت ابزارهای ساختمان')

@section('content')
<style>
.tools-head{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:18px}
.tools-head h2{margin:0;font-size:1.12rem}
.tools-head p{margin:7px 0 0;color:var(--muted);font-size:13px;line-height:1.9;max-width:760px}
.tools-groups{display:grid;gap:16px}
.tools-group{border:1px solid var(--line);border-radius:15px;overflow:hidden}
.tools-group h3{margin:0;padding:13px 16px;background:var(--surface-2);font-size:.94rem}
.tools-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 18px;padding:4px 16px}
.tool-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 0;border-bottom:1px solid var(--line)}
.tool-row:last-child{border-bottom:0}
.tool-name{font-weight:750;font-size:13px}
.tool-state{display:flex;align-items:center;gap:9px;white-space:nowrap;font-size:12px;color:var(--muted)}
.tool-state input{width:19px;height:19px;accent-color:var(--brand,#1c4f4a)}
.tools-footer{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:18px}
.tools-note{font-size:12px;color:var(--muted);line-height:1.8}
@media(max-width:700px){.tools-list{grid-template-columns:1fr}}
</style>
<div class="card">
  <div class="tools-head">
    <div>
      <h2>فعال‌سازی و غیرفعال‌سازی ابزارهای مدیریت ساختمان</h2>
      <p>هر ابزاری که غیرفعال شود، از منوی کناری کاربران حذف می‌شود و دسترسی مستقیم به صفحه‌های آن هم بسته خواهد شد. این تنظیمات برای همهٔ ساختمان‌ها اعمال می‌شود.</p>
    </div>
    <span class="tag-adm">{{ collect($states)->filter()->count() }} ابزار فعال از {{ count($states) }}</span>
  </div>

  <form method="POST" action="{{ route('admin.building-tools.update') }}">
    @csrf
    @method('PUT')
    <div class="tools-groups">
      @foreach(collect($tools)->groupBy('group') as $groupName => $groupTools)
        <section class="tools-group">
          <h3>{{ $groupName }}</h3>
          <div class="tools-list">
            @foreach($groupTools as $tool)
              <label class="tool-row">
                <span class="tool-name">{{ $tool['label'] }}</span>
                <span class="tool-state">
                  <input type="checkbox" name="tools[]" value="{{ $tool['key'] }}" @checked($states[$tool['key']])>
                  {{ $states[$tool['key']] ? 'فعال' : 'غیرفعال' }}
                </span>
              </label>
            @endforeach
          </div>
        </section>
      @endforeach
    </div>
    @error('tools.*')<div class="e" style="margin-top:12px">{{ $message }}</div>@enderror
    <div class="tools-footer">
      <span class="tools-note">داشبورد و اشتراک برای دسترسی پایه همیشه فعال می‌مانند. قالب‌های پیامک به‌صورت پیش‌فرض غیرفعال هستند.</span>
      <button class="btn" type="submit">ذخیره تغییرات</button>
    </div>
  </form>
</div>
@endsection
