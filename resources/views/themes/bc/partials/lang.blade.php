@php
    $langs = \App\Support\Locale::all();
    $showLang = count($langs) > 1 && app(\App\Services\Cms\ThemeCatalog::class)->supportsI18n();
@endphp
@if($showLang)
    <span class="lang">
        @foreach($langs as $code => $meta)
            @php
                $native = $meta['native'] ?? $meta['label'] ?? $code;
                $short = $native;
                if (\App\Support\Locale::isEnglish()) {
                    $short = str_starts_with((string) $code, 'zh') ? '中文' : (str_starts_with((string) $code, 'en') ? 'EN' : $native);
                }
            @endphp
            <a href="{{ cms_locale_url($code) }}" @if($code === \App\Support\Locale::current()) aria-current="page" @endif>{{ $short }}</a>@unless($loop->last) · @endunless
        @endforeach
    </span>
@endif
