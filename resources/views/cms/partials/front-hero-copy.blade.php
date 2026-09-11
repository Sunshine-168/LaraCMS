@php
    $heroTitle = trim((string) ($site['hero_title'] ?? ''));
    $heroSub = trim((string) ($site['hero_subtitle'] ?? ''));
    if ($heroTitle === '') {
        $heroTitle = (string) ($fallbackTitle ?? '');
    }
    if ($heroSub === '') {
        $heroSub = (string) ($fallbackSub ?? '');
    }
@endphp
@if($heroTitle !== '')
    <h1>{{ $heroTitle }}</h1>
@endif
@if($heroSub !== '')
    <p class="muted">{{ $heroSub }}</p>
@endif
