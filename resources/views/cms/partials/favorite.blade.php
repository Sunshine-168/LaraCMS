@php
    $entry = $entry ?? $article ?? null;
    $favMember = auth('member')->user();
@endphp
@if($entry)
<section class="cms-favorite" style="margin-top:20px">
    @if($favMember)
        <form method="post" action="{{ url(\App\Support\UrlMode::localePrefix().'/member/favorite/'.$entry->id) }}" style="display:inline">
            @csrf
            <button type="submit">{{ $favMember->hasFavorited($entry->id) ? '取消收藏' : '收藏本文' }}</button>
        </form>
    @else
        <p class="muted"><a href="{{ cms_front_url('member/login') }}">登录</a> 后可收藏本文</p>
    @endif
</section>
@endif
