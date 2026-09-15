@if($paginator->hasPages())
    <div class="muted">
        @if($paginator->onFirstPage())
            <span>{{ cms_ui('prev_page') }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}">{{ cms_ui('prev_page') }}</a>
        @endif
        <span style="margin:0 12px">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">{{ cms_ui('next_page') }}</a>
        @else
            <span>{{ cms_ui('next_page') }}</span>
        @endif
    </div>
@endif
