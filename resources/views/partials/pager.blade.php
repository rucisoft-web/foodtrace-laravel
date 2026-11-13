@if ($paginator->hasPages())
<div class="pager">
  @if ($paginator->onFirstPage())<span class="muted">‹</span>@else<a href="{{ $paginator->previousPageUrl() }}">‹</a>@endif
  @if(method_exists($paginator, 'lastPage'))
    @foreach (range(max(1, $paginator->currentPage() - 3), min($paginator->lastPage(), $paginator->currentPage() + 3)) as $p)
      @if ($p == $paginator->currentPage())<span class="on">{{ $p }}</span>@else<a href="{{ $paginator->url($p) }}">{{ $p }}</a>@endif
    @endforeach
    <span class="muted" style="border:0;background:none">dari {{ $paginator->lastPage() }} hal · {{ number_format($paginator->total()) }} baris</span>
  @endif
  @if ($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}">›</a>@else<span class="muted">›</span>@endif
</div>
@endif
