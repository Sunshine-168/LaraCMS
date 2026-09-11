@if(!empty($canContribute))
    <h2 style="margin-top:28px">我的投稿</h2>
    <p>
        <a href="{{ cms_route('member.entries.index') }}">全部投稿</a>
        @if(($contributeModels ?? collect())->isNotEmpty())
            · <a href="{{ cms_route('member.entries.create', ['model_id' => $contributeModels->first()->id]) }}">写新稿</a>
        @endif
    </p>
    @if(($myEntries ?? null) && $myEntries->isNotEmpty())
        <ul style="padding-left:18px">
            @foreach($myEntries as $item)
                <li style="margin:8px 0">
                    {{ $item->t('title') }}
                    <span class="muted"> · {{ \App\Models\Cms\Entry::statusLabel($item->status) }}</span>
                    @if($item->status === 'published')
                        · <a href="{{ $item->url }}">查看</a>
                    @else
                        · <a href="{{ cms_route('member.entries.edit', $item) }}">编辑</a>
                    @endif
                </li>
            @endforeach
        </ul>
        {{ $myEntries->links() }}
    @else
        <p class="muted">还没有投稿。</p>
    @endif
@endif

@if(($favorites ?? null) && $favorites->isNotEmpty())
    <h2 style="margin-top:28px">我的收藏</h2>
    <ul style="padding-left:18px">
        @foreach($favorites as $item)
            <li style="margin:8px 0">
                <a href="{{ $item->url }}">{{ $item->t('title') }}</a>
                <form method="post" action="{{ url(\App\Support\UrlMode::localePrefix().'/member/favorite/'.$item->id) }}" style="display:inline;margin-left:8px">
                    @csrf
                    <button type="submit" class="muted">取消</button>
                </form>
            </li>
        @endforeach
    </ul>
    {{ $favorites->links() }}
@else
    <h2 style="margin-top:28px">我的收藏</h2>
    <p class="muted">还没有收藏。</p>
@endif

@if(($comments ?? null) && $comments->isNotEmpty())
    <h2 style="margin-top:28px">我的评论</h2>
    <ul style="padding-left:18px">
        @foreach($comments as $comment)
            <li style="margin:8px 0">
                <span class="muted">{{ $comment->created_at?->format('Y-m-d H:i') }} · {{ \App\Models\Cms\Comment::statusLabel($comment->status) }}</span>
                @if($comment->entry)
                    · <a href="{{ $comment->entry->url }}">{{ $comment->entry->t('title') }}</a>
                @endif
                <div>{{ \Illuminate\Support\Str::limit($comment->content, 120) }}</div>
            </li>
        @endforeach
    </ul>
    {{ $comments->links() }}
@endif

@if(($submissions ?? null) && $submissions->isNotEmpty())
    <h2 style="margin-top:28px">我的留言</h2>
    <ul style="padding-left:18px">
        @foreach($submissions as $row)
            <li style="margin:8px 0">
                <span class="muted">{{ $row->created_at?->format('Y-m-d H:i') }}</span>
                @if($row->form) · {{ $row->form->name }}@endif
                <div class="muted">{{ \Illuminate\Support\Str::limit(collect($row->data ?? [])->map(fn ($v) => is_array($v) ? implode(',', $v) : $v)->implode(' / '), 120) }}</div>
            </li>
        @endforeach
    </ul>
    {{ $submissions->links() }}
@endif

@if(($memberEntries ?? null) && $memberEntries->isNotEmpty())
    <h2 style="margin-top:28px">会员内容</h2>
    <ul style="padding-left:18px">
        @foreach($memberEntries as $item)
            <li style="margin:8px 0"><a href="{{ $item->url }}">{{ $item->t('title') }}</a></li>
        @endforeach
    </ul>
@endif
