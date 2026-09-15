    @cmsBreadcrumb
    <h1>{{ $entry->t('title') }}</h1>
    @php $manga = \App\Models\Cms\Entry::query()->published()->with(['category', 'modelDef'])->find((int) $entry->attr('manga_id')); @endphp
    <p class="muted">
        @if($manga)
            <a href="{{ $manga->url }}">{{ $manga->t('title') }}</a>
        @else
            <a href="{{ cms_front_url('comics') }}">{{ cms_ui('back_comics') }}</a>
        @endif
    </p>
    <div class="pages" style="margin-top:16px">
        @foreach(preg_split('/\r\n|\r|\n/', (string)$entry->attr('pages')) as $img)
            @if(trim($img) !== '')
                <img src="{{ cms_media(trim($img)) }}" alt="{{ $entry->t('title') }}" loading="lazy">
            @endif
        @endforeach
    </div>
    <p style="margin:20px 0">{{ cms_ui('prev_chapter') }}：@cmsPrev · {{ cms_ui('next_chapter') }}：@cmsNext</p>
    @include('cms.comments')
