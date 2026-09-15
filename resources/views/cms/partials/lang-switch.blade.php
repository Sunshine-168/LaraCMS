@php
    $langs = \App\Support\Locale::all();
@endphp
@if(count($langs) > 1)
    <span class="lang">
        @foreach($langs as $code => $meta)
            <a href="{{ cms_locale_url($code) }}" @if($code === \App\Support\Locale::current()) aria-current="page" @endif>{{ $meta['native'] ?? $meta['label'] ?? $code }}</a>@unless($loop->last) · @endunless
        @endforeach
    </span>
@endif
