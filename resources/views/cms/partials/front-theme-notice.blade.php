@php
    $notice = cms_theme('home_notice');
@endphp
@if($notice !== '')
    <p class="theme-home-notice muted" style="margin:12px 0;padding:10px 12px;border:1px solid var(--line,#dbe3ee);border-radius:8px;background:rgba(255,255,255,.7)">{{ $notice }}</p>
@endif
