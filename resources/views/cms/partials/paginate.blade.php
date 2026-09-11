@if($paginator->hasPages())
    <div class="muted">
        @if($paginator->onFirstPage())
            <span>Prev</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}">Prev</a>
        @endif
        <span style="margin:0 12px">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">Next</a>
        @else
            <span>Next</span>
        @endif
    </div>
@endif
