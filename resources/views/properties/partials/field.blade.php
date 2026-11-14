@php
    /**
     * @var string $name  @var string $label
     * اختیاری: $type, $required, $placeholder, $suffix, $help, $money, $min, $max, $maxlength, $mode, $show (data-show-for)
     */
    $listing = $listing ?? null;
    $value = old($name, $listing?->{$name});
    $type = $type ?? 'text';
    $money = $money ?? false;
    $mode = $mode ?? ($type === 'number' ? 'numeric' : null);
    $hasError = $errors->has($name);
@endphp
<div class="fld @if($hasError) has-error @endif" @isset($show) data-show-for="{{ $show }}" @endisset>
    <label for="f-{{ $name }}">{{ $label }}@if(! empty($required))<i class="req" title="الزامی">*</i>@endif</label>
    <div class="ctl">
        <input id="f-{{ $name }}" name="{{ $name }}" type="{{ $money || $type === 'number' ? 'text' : $type }}"
            value="{{ $money && is_numeric($value) ? number_format((float) $value, 0, '.', ',') : $value }}"
            @if($mode) inputmode="{{ $mode }}" @endif
            @if($money) data-money autocomplete="off" @endif
            @if($type === 'number' && ! $money) data-digits @endif
            @if(! empty($required)) required @endif
            @isset($maxlength) maxlength="{{ $maxlength }}" @endisset
            @isset($placeholder) placeholder="{{ $placeholder }}" @endisset
            @if($hasError) aria-invalid="true" aria-describedby="e-{{ $name }}" @endif>
        @isset($suffix)<span class="sfx">{{ $suffix }}</span>@endisset
    </div>
    @if($money)<small class="words" data-words-for="f-{{ $name }}" aria-live="polite"></small>@endif
    @isset($help)<small class="help">{{ $help }}</small>@endisset
    @error($name)<small class="ferr" id="e-{{ $name }}" role="alert">{{ $message }}</small>@enderror
</div>
