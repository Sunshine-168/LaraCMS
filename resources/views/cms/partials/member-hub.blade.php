@if(!empty($canContribute))
    <h2 style="margin-top:28px">{{ cms_ui('my_posts') }}</h2>
    <p>
        <a href="{{ cms_route('member.entries.index') }}">{{ cms_ui('all_posts') }}</a>
        @if(($contributeModels ?? collect())->isNotEmpty())
            · <a href="{{ cms_route('member.entries.create', ['model_id' => $contributeModels->first()->id]) }}">{{ cms_ui('write_post') }}</a>
        @endif
    </p>
    @if(($myEntries ?? null) && $myEntries->isNotEmpty())
        <ul style="padding-left:18px">
            @foreach($myEntries as $item)
                <li style="margin:8px 0">
                    {{ $item->t('title') }}
                    <span class="muted"> · {{ cms_ui('status_'.$item->status) }}</span>
                    @if($item->status === 'published')
                        · <a href="{{ $item->url }}">{{ cms_ui('view') }}</a>
                    @else
                        · <a href="{{ cms_route('member.entries.edit', $item) }}">{{ cms_ui('edit') }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
        {{ $myEntries->links('cms.partials.paginate') }}
    @else
        <p class="muted">{{ cms_ui('no_posts') }}</p>
    @endif
@endif

@if(($favorites ?? null) && $favorites->isNotEmpty())
    <h2 style="margin-top:28px">{{ cms_ui('my_favorites') }}</h2>
    <ul style="padding-left:18px">
        @foreach($favorites as $item)
            <li style="margin:8px 0">
                <a href="{{ $item->url }}">{{ $item->t('title') }}</a>
                <form method="post" action="{{ cms_route('member.favorite', $item) }}" style="display:inline;margin-left:8px">
                    @csrf
                    <button type="submit" class="muted">{{ cms_ui('unfavorite_short') }}</button>
                </form>
            </li>
        @endforeach
    </ul>
    {{ $favorites->links('cms.partials.paginate') }}
@else
    <h2 style="margin-top:28px">{{ cms_ui('my_favorites') }}</h2>
    <p class="muted">{{ cms_ui('no_favorites') }}</p>
@endif

@if(($comments ?? null) && $comments->isNotEmpty())
    <h2 style="margin-top:28px">{{ cms_ui('my_comments') }}</h2>
    <ul style="padding-left:18px">
        @foreach($comments as $comment)
            <li style="margin:8px 0">
                <span class="muted">{{ $comment->created_at?->format('Y-m-d H:i') }} · {{ cms_ui('comment_'.$comment->status) }}</span>
                @if($comment->entry)
                    · <a href="{{ $comment->entry->url }}">{{ $comment->entry->t('title') }}</a>
                @endif
                <div>{{ \Illuminate\Support\Str::limit($comment->content, 120) }}</div>
            </li>
        @endforeach
    </ul>
    {{ $comments->links('cms.partials.paginate') }}
@endif

@if(($submissions ?? null) && $submissions->isNotEmpty())
    <h2 style="margin-top:28px">{{ cms_ui('my_inbox') }}</h2>
    <ul style="padding-left:18px">
        @foreach($submissions as $row)
            <li style="margin:8px 0">
                <span class="muted">{{ $row->created_at?->format('Y-m-d H:i') }}</span>
                @if($row->form) · {{ $row->form->name }}@endif
                <div class="muted">{{ \Illuminate\Support\Str::limit(collect($row->data ?? [])->map(fn ($v) => is_array($v) ? implode(',', $v) : $v)->implode(' / '), 120) }}</div>
            </li>
        @endforeach
    </ul>
    {{ $submissions->links('cms.partials.paginate') }}
@endif

@if(($memberEntries ?? null) && $memberEntries->isNotEmpty())
    <h2 style="margin-top:28px">{{ cms_ui('member_content') }}</h2>
    <ul style="padding-left:18px">
        @foreach($memberEntries as $item)
            <li style="margin:8px 0"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></li>
        @endforeach
    </ul>
@endif
