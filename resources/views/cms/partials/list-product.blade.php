    @cmsBreadcrumb
    <h1 style="margin:16px 0">{{ $category->t('title') }}</h1>
    <div class="grid">
        @cmsContent(['page' => true, 'num' => $category->page_size ?: 12])
            <article class="card-soft">
                @if($item->cover)<img src="{{ cms_media($item->cover) }}" alt="" style="width:100%;height:160px;object-fit:cover;border-radius:8px">@endif
                <h3><a href="{{ $item->url }}">{{ $item->t('title') }}</a></h3>
                <p class="muted">{{ $item->attr('price') }}</p>
            </article>
        @endcmsContent
    </div>
    <div style="margin:20px 0">@cmsPaginate</div>
