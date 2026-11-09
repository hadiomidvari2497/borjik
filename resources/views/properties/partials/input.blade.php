@php
    /** فیلد متنی/عددی فرم آگهی: برچسب، علامت اجباری، پسوند، راهنما و خطای کنار فیلد. */
    $id = 'f-'.$name;
    $hasErr = $errors->has($name);
    $suffix = $suffix ?? null;
    $money = $money ?? false;
    $required = $required ?? false;
    $labels = $labels ?? [];
    $hint = $hint ?? null;
    $inputmode = $inputmode ?? ($money ? 'numeric' : null);
    $describedBy = trim(($hint ? $id.'-hint ' : '').($hasErr ? $id.'-err' : ''));
@endphp
<label class="field @if($hasErr) has-error @endif" for="{{ $id }}">
    <span class="lbl">
        <span class="lbl-text" @foreach($labels as $t => $text) data-l-{{ $t }}="{{ $text }}" @endforeach>{{ $label }}</span>
        @if($required)<b class="req" aria-hidden="true">*</b><span class="sr-only">(اجباری)</span>@endif
    </span>
    <span class="affix @if($suffix) has-suffix @endif">
        <input id="{{ $id }}" name="{{ $name }}" type="text"
            @if($inputmode) inputmode="{{ $inputmode }}" @endif
            @if($money) data-money @endif
            @if(! empty($maxlength)) maxlength="{{ $maxlength }}" @endif
            @if($required) required @endif
            @if($hasErr) aria-invalid="true" @endif
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @class(['is-invalid' => $hasErr])
            value="{{ old($name, $value ?? '') }}"
            placeholder="{{ $placeholder ?? '' }}" autocomplete="off">
        @if($suffix)<em class="suffix" aria-hidden="true">{{ $suffix }}</em>@endif
    </span>
    @if($hint)<span class="form-help" id="{{ $id }}-hint">{{ $hint }}</span>@endif
    @if($hasErr)<span class="field-error" id="{{ $id }}-err" role="alert">{{ $errors->first($name) }}</span>@endif
</label>
