@extends($layout)

@section('content')
<section class="wrap" style="max-width:720px;margin:40px auto">
    <h1>我的投稿</h1>
    @if(session('status'))<p style="color:green">{{ session('status') }}</p>@endif
    @if(session('error'))<p style="color:#c00">{{ session('error') }}</p>@endif
    <p>
        <a href="{{ route('member.profile') }}">返回会员中心</a>
        @if(($models ?? collect())->isNotEmpty())
            · <a href="{{ route('member.entries.create', ['model_id' => $models->first()->id]) }}">写新稿</a>
        @endif
    </p>
    @if(($entries ?? null) && $entries->isNotEmpty())
        <ul style="padding-left:18px">
            @foreach($entries as $item)
                <li style="margin:10px 0">
                    <strong>{{ $item->t('title') }}</strong>
                    <span class="muted"> · {{ \App\Models\Cms\Entry::statusLabel($item->status) }}
                        @if($item->category) · {{ $item->category->t('title') }}@endif
                    </span>
                    @if($item->status === 'published')
                        · <a href="{{ $item->url }}">查看</a>
                    @else
                        · <a href="{{ route('member.entries.edit', $item) }}">编辑</a>
                    @endif
                </li>
            @endforeach
        </ul>
        {{ $entries->links() }}
    @else
        <p class="muted">还没有投稿。</p>
    @endif
</section>
@endsection
