<section class="wrap" style="max-width:720px;margin:40px auto">
    <h1>{{ cms_ui('my_posts') }}</h1>
    @if(session('status'))<p style="color:green">{{ session('status') }}</p>@endif
    @if(session('error'))<p style="color:#c00">{{ session('error') }}</p>@endif
    <p>
        <a href="{{ cms_route('member.profile') }}">{{ cms_ui('member_center') }}</a>
        @if(($models ?? collect())->isNotEmpty())
            · <a href="{{ cms_route('member.entries.create', ['model_id' => $models->first()->id]) }}">{{ cms_ui('write_post') }}</a>
        @endif
    </p>
    @if(($entries ?? null) && $entries->isNotEmpty())
        <ul style="padding-left:18px">
            @foreach($entries as $item)
                <li style="margin:10px 0">
                    <strong>{{ $item->t('title') }}</strong>
                    <span class="muted"> · {{ cms_ui('status_'.$item->status) }}
                        @if($item->category) · {{ $item->category->t('title') }}@endif
                    </span>
                    @if($item->status === 'published')
                        · <a href="{{ $item->url }}">{{ cms_ui('view') }}</a>
                    @else
                        · <a href="{{ cms_route('member.entries.edit', $item) }}">{{ cms_ui('edit') }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
        {{ $entries->links('cms.partials.paginate') }}
    @else
        <p class="muted">{{ cms_ui('no_posts') }}</p>
    @endif
</section>
