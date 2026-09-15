@php
    $entry = $entry ?? $article ?? null;
    $favMember = auth('member')->user();
@endphp
@if($entry)
<section class="cms-favorite" style="margin-top:20px">
    @if($favMember)
        <form method="post" action="{{ cms_route('member.favorite', $entry) }}" style="display:inline">
            @csrf
            <button class="btn" type="submit">{{ $favMember->hasFavorited($entry->id) ? cms_ui('unfavorite') : cms_ui('favorite') }}</button>
        </form>
    @else
        <p class="muted">{!! str_replace(':link', '<a href="'.e(cms_front_url('member/login')).'">'.e(cms_ui('login')).'</a>', e(cms_ui('favorite_login'))) !!}</p>
    @endif
</section>
@endif
