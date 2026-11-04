@extends('admin.layout')
@section('title', 'تنظیمات بازارچه آگهی')
@section('content')
@php
    $packages = old('packages', $packages);
@endphp
<div class="card">
    <div class="page-h">
        <h2>تنظیمات بازارچه آگهی‌های ملکی</h2>
        <span class="pill">مدیریت سهمیه و بسته‌ها</span>
    </div>
    <p style="color:var(--muted);font-size:13px;line-height:1.8">
        سهمیه بر اساس حساب کاربری محاسبه می‌شود و به نقش کاربر در ساختمان ارتباطی ندارد.
        آگهی‌های غیرفعال و بسته‌شده در سهمیه آگهی فعال محاسبه نمی‌شوند.
    </p>
    <form method="POST" action="{{ route('admin.property-listings.settings.update') }}">
        @csrf
        @method('PUT')
        <div class="fgrid">
            <div class="fld">
                <label for="free_limit">تعداد آگهی فعال رایگان</label>
                <input id="free_limit" name="free_limit" type="number" min="0" max="100" value="{{ old('free_limit', $freeLimit) }}" required>
                <small style="color:var(--muted)">عدد صفر یعنی ثبت آگهی رایگان غیرفعال است.</small>
                @error('free_limit')<div class="e">{{ $message }}</div>@enderror
            </div>
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin:24px 0 12px">
            <h3 style="margin:0">بسته‌های آگهی اضافی</h3>
            <button class="btn sec sm" type="button" id="add-package">افزودن بسته</button>
        </div>
        <div id="packages" class="fgrid">
            @foreach($packages as $index => $package)
                <div class="fld" style="border:1px solid var(--border);border-radius:12px;padding:14px;position:relative">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                        <b>بسته آگهی</b>
                        <button type="button" class="btn dng sm remove-package">حذف</button>
                    </div>
                    <label>تعداد آگهی</label>
                    <input type="number" name="packages[{{ $index }}][quantity]" min="1" max="1000" value="{{ $package['quantity'] }}" required>
                    <label style="margin-top:10px">قیمت (تومان)</label>
                    <input type="number" name="packages[{{ $index }}][price]" min="0" max="1000000000" value="{{ $package['price'] }}" required>
                    <label style="display:flex;gap:8px;align-items:center;margin-top:12px">
                        <input type="checkbox" name="packages[{{ $index }}][active]" value="1" style="width:auto" @checked($package['active'] ?? false)>
                        فعال و قابل خرید
                    </label>
                </div>
            @endforeach
        </div>
        @error('packages')<div class="e">{{ $message }}</div>@enderror
        @error('packages.*.quantity')<div class="e">{{ $message }}</div>@enderror
        @error('packages.*.price')<div class="e">{{ $message }}</div>@enderror

        <div class="fbar" style="margin-top:20px">
            <button class="btn" type="submit">ذخیره تنظیمات</button>
        </div>
    </form>
</div>
<script>
(() => {
    const container = document.getElementById('packages');
    const add = document.getElementById('add-package');
    if (!container || !add) return;
    const reindex = () => [...container.children].forEach((card, index) => {
        card.querySelectorAll('input').forEach(input => {
            input.name = input.name.replace(/packages\\[\\d+\\]/, 'packages[' + index + ']');
        });
    });
    container.addEventListener('click', event => {
        if (event.target.closest('.remove-package')) {
            event.target.closest('.fld').remove();
            reindex();
        }
    });
    add.addEventListener('click', () => {
        const index = container.children.length;
        const card = document.createElement('div');
        card.className = 'fld';
        card.style.cssText = 'border:1px solid var(--border);border-radius:12px;padding:14px;position:relative';
        card.innerHTML = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px"><b>بسته آگهی</b><button type="button" class="btn dng sm remove-package">حذف</button></div><label>تعداد آگهی</label><input type="number" name="packages['+index+'][quantity]" min="1" max="1000" value="1" required><label style="margin-top:10px">قیمت (تومان)</label><input type="number" name="packages['+index+'][price]" min="0" max="1000000000" value="0" required><label style="display:flex;gap:8px;align-items:center;margin-top:12px"><input type="checkbox" name="packages['+index+'][active]" value="1" checked style="width:auto">فعال و قابل خرید</label>';
        container.appendChild(card);
    });
})();
</script>
@endsection
