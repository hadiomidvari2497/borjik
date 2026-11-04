@php($fqLayout = ($columns ?? 1) === 2 ? 'fq-2' : 'fq-narrow')
<div class="fq {{ $fqLayout }} {{ $class ?? '' }}">
@foreach($items as $item)
    <details class="fq-item"><summary class="fq-q"><span>{{ $item['q'] }}</span></summary><div class="fq-a"><p>{{ $item['a'] }}</p></div></details>
@endforeach
</div>
