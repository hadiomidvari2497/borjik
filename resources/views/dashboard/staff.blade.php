@php
  $pri = ['urgent' => ['اضطراری', 'u'], 'high' => ['زیاد', 'h'], 'medium' => ['متوسط', ''], 'low' => ['کم', '']]; @endphp
@extends('layouts.app')
@section('title', 'کارهای من')
@section('content')
<div class="r2">
  <div class="card">
    <div class="hd"><h2 style="margin:0">تسک‌های ارجاع‌شده به من</h2><small>{{ \App\Support\Fa::num($tasks->count()) }} مورد باز</small></div>
    @forelse($tasks as $task)
      <div class="kv">
        <div><b>{{ $task->title }}</b><br><span>{{ $task->location }} @if($task->deadline)· مهلت {{ \App\Support\Fa::date($task->deadline) }}@endif</span></div>
        <span class="pill {{ $pri[$task->priority->value][1] }}">{{ $pri[$task->priority->value][0] }}</span>
      </div>
    @empty
      <div class="empty">تسک بازی ندارید.</div>
    @endforelse
  </div>
  <div class="card lst">
    <h2>رویدادهای نزدیک</h2>
    @forelse($events as $e)
      <div class="evt"><div class="d">{{ \App\Support\Fa::digits(\Morilog\Jalali\Jalalian::fromCarbon($e->starts_at)->format('j')) }}<small>{{ \App\Support\Fa::MONTHS[\Morilog\Jalali\Jalalian::fromCarbon($e->starts_at)->getMonth() - 1] }}</small></div>
        <div><b>{{ $e->title }}</b><span>{{ \App\Support\Fa::digits($e->starts_at->format('H:i')) }}</span></div></div>
    @empty
      <div class="empty">رویدادی در پیش نیست.</div>
    @endforelse
  </div>
</div>
@endsection
