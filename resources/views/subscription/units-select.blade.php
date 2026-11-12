@extends('layouts.app')
@section('title', 'انتخاب واحدهای فعال اشتراک')
@section('content')
<div class="card" style="max-width:1000px;margin:0 auto">
    <div class="page-h">
        <div>
            <h2>انتخاب واحدهای فعال اشتراک</h2>
            <p style="color:var(--muted);margin-top:6px">ساختمان «{{ $building->name }}» ظرفیت {{ \App\Support\Fa::num($subscription->units_limit) }} واحد دارد. واحدهای انتخاب‌نشده حذف نمی‌شوند و سوابق آن‌ها محفوظ می‌ماند، اما عملیات روزمره برای آن‌ها غیرفعال خواهد بود.</p>
        </div>
    </div>
    @if(session('warning'))<div class="warnbox" role="alert">{{ session('warning') }}</div>@endif
    @if($errors->any())<div class="warnbox" role="alert">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('subscription.units.store') }}" id="active-units-form">
        @csrf
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:16px 0">
            <strong>واحدهای انتخاب‌شده: <span id="selected-count">{{ \App\Support\Fa::num(count($selectedIds)) }}</span> از {{ \App\Support\Fa::num($requiredCount) }}</strong>
            <span style="color:var(--muted)">برای ادامه دقیقاً {{ \App\Support\Fa::num($requiredCount) }} واحد انتخاب کنید.</span>
        </div>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:10px">
            @foreach($units as $unit)
                <label class="card" style="display:flex;align-items:center;gap:10px;padding:12px;cursor:pointer">
                    <input type="checkbox" name="unit_ids[]" value="{{ $unit->id }}" @checked(in_array($unit->id, old('unit_ids', $selectedIds)))>
                    <span><strong>{{ $unit->block?->name }} - {{ \App\Support\Fa::digits($unit->number) }}</strong><small style="display:block;color:var(--muted)">طبقه {{ \App\Support\Fa::digits($unit->floor) }}</small></span>
                </label>
            @endforeach
        </div>
        <div style="display:flex;justify-content:flex-end;margin-top:20px">
            <button class="btn" type="submit" id="save-active-units">ذخیره واحدهای فعال</button>
        </div>
    </form>
</div>
<script>
(() => {
    const form = document.getElementById('active-units-form');
    if (!form) return;
    const checks = [...form.querySelectorAll('input[name="unit_ids[]"]')];
    const count = document.getElementById('selected-count');
    const max = {{ (int) $requiredCount }};
    const submit = document.getElementById('save-active-units');
    const update = () => {
        const selected = checks.filter(item => item.checked).length;
        count.textContent = new Intl.NumberFormat('fa-IR').format(selected);
        submit.disabled = selected !== max;
        checks.forEach(item => { item.disabled = !item.checked && selected >= max; });
    };
    checks.forEach(item => item.addEventListener('change', update));
    update();
})();
</script>
@endsection
