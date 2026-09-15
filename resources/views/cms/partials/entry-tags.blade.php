@php
    $entry->loadMissing('tags');
    $tags = $entry->tags ?? collect();
@endphp
@if($tags->isNotEmpty())
    <p class="muted cms-entry-tags" style="margin-top:16px">{{ cms_ui('keywords') }}：
        @foreach($tags as $tag)
            <a href="{{ cms_front_url('tag/'.$tag->t('slug')) }}">{{ $tag->t('title') }}</a>@unless($loop->last) · @endunless
        @endforeach
    </p>
@endif
