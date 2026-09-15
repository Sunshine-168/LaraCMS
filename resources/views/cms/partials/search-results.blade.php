@if(($q ?? '') !== '')
    <p class="muted">{{ cms_ui('keywords') }}：{{ $q }}</p>
    <div class="{{ $searchListClass ?? '' }}">
        @forelse($results as $item)
            <article class="{{ $searchItemClass ?? 'card-soft' }}">
                <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
                <p class="muted">@cmsSubstr(['name' => $item->t('excerpt') ?: $item->t('content'), 'len' => 100])</p>
            </article>
        @empty
            <p class="muted">{{ cms_ui('no_results') }}</p>
        @endforelse
    </div>
    <div style="margin-top:16px">@cmsPaginate</div>
@endif
