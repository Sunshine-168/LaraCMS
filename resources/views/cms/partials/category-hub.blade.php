    @cmsBreadcrumb
    <h1 style="margin:16px 0">{{ $category->t('title') }}</h1>
    @if($category->t('meta_description'))
        <p class="muted">{{ $category->t('meta_description') }}</p>
    @endif
    <div class="grid">
        @cmsChannel(['type' => 'son'])
            <a class="card-soft" href="{{ $item->frontUrl() }}">
                <h3>{{ $item->t('title') }}</h3>
                @if($item->t('meta_description'))
                    <p class="muted">{{ $item->t('meta_description') }}</p>
                @endif
            </a>
        @endcmsChannel
    </div>
