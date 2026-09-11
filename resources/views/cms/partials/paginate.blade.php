@if($paginator->hasPages())
    @php
        $prev = \App\Support\Locale::isEnglish() ? 'Previous' : '上一页';
        $next = \App\Support\Locale::isEnglish() ? 'Next' : '下一页';
    @endphp
    <div class="muted">
        @if($paginator->onFirstPage())
            <span>{{ $prev }}</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}">{{ $prev }}</a>
        @endif
        <span style="margin:0 12px">{{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}</span>
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">{{ $next }}</a>
        @else
            <span>{{ $next }}</span>
        @endif
    </div>
@endif
