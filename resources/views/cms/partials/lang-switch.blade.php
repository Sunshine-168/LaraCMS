@php
    $langs = \App\Support\Locale::all();
    $showLang = count($langs) > 1 && app(\App\Services\Cms\ThemeCatalog::class)->supportsI18n();
@endphp
@if($showLang)
    <span class="lang">
        @foreach($langs as $code => $meta)
            <a href="{{ cms_locale_url($code) }}" @if($code === \App\Support\Locale::current()) aria-current="page" @endif>{{ $meta['native'] ?? $meta['label'] ?? $code }}</a>@unless($loop->last) · @endunless
        @endforeach
    </span>
@endif
