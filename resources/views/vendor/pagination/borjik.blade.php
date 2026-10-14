@if ($paginator->hasPages())
<nav class="pg" role="navigation" aria-label="صفحه‌بندی">
  @if ($paginator->onFirstPage())<span class="off">قبلی</span>@else<a href="{{ $paginator->previousPageUrl() }}" rel="prev">قبلی</a>@endif
  <span class="cur">{{ \App\Support\Fa::digits($paginator->currentPage()) }} از {{ \App\Support\Fa::digits($paginator->lastPage()) }}</span>
  @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" rel="next">بعدی</a>@else<span class="off">بعدی</span>@endif
</nav>
@endif
