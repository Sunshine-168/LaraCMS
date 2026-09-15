@php
    $heroTitle = trim((string) ($site['hero_title'] ?? ''));
    $heroSub = trim((string) ($site['hero_subtitle'] ?? ''));
    if ($heroTitle === '') {
        $heroTitle = cms_theme('home_title');
    }
    if ($heroTitle === '') {
        $heroTitle = (string) ($fallbackTitle ?? '');
    }
    if ($heroSub === '') {
        $heroSub = cms_theme('home_sub');
    }
    if ($heroSub === '') {
        $heroSub = (string) ($fallbackSub ?? '');
    }
    $ctaText = cms_theme('cta_text');
    $ctaUrl = trim(cms_theme('cta_url'));
    if ($ctaUrl === '' || $ctaUrl === '/') {
        $ctaUrl = cms_front_url();
    } elseif (! preg_match('#^(https?:)?//#i', $ctaUrl) && ! str_starts_with($ctaUrl, '#')) {
        $ctaUrl = cms_front_url(ltrim($ctaUrl, '/'));
    }
@endphp
@if($heroTitle !== '')
    <h1>{{ $heroTitle }}</h1>
@endif
@if($heroSub !== '')
    <p class="muted">{{ $heroSub }}</p>
@endif
@if($ctaText !== '')
    <p><a class="btn" href="{{ $ctaUrl }}">{{ $ctaText }}</a></p>
@endif
