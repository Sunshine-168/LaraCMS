@if($paginator->hasPages())
    <div class="muted">
        @if($paginator->onFirstPage())
            <span>上一页</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}">上一页</a>
        @endif
        <span style="margin:0 12px">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">下一页</a>
        @else
            <span>下一页</span>
        @endif
    </div>
@endif
