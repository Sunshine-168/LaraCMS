@php
    $names = $names ?? ['friend_links', 'partners'];
    $__friendShown = false;
@endphp
@foreach ($names as $advName)
    @cmsAdv(['name' => $advName])
        @if(! $__friendShown)
            <section class="cms-friend-links" style="margin:32px 0">
                <h2>{{ cms_ui('friend_links') }}</h2>
                <div class="cms-friend-links-list" style="display:flex;flex-wrap:wrap;gap:16px;margin-top:12px">
            @php $__friendShown = true; @endphp
        @endif
        @if($item->html)
            {!! $item->html !!}
        @else
            <a href="{{ $item->url ?: '#' }}" class="card-soft" style="min-width:120px;text-align:center" rel="noopener nofollow" target="_blank">
                @if($item->image)<img src="{{ $item->image }}" alt="" style="max-height:40px">@endif
                <div>{{ $item->t('title') }}</div>
            </a>
        @endif
    @endcmsAdv
@endforeach
@if($__friendShown)
                </div>
            </section>
@endif
