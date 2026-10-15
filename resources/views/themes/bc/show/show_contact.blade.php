@extends('themes.bc.partials.layout')

@section('content')
    <header class="ig-pagehead">
        <div class="ig-wrap">
            @include('themes.bc.partials.crumb')
            <h1>{{ $entry->t('title') }}</h1>
        </div>
    </header>
    <div class="ig-wrap bc-page ig-contact">
        <div class="bc-prose">
            @php
                $body = $entry->linked_content ?? $entry->t('content');
                $localePrefix = \App\Support\UrlMode::localePrefix();
                if ($localePrefix !== '') {
                    $body = preg_replace('#href="(/gw-[^"]*)"#', 'href="'.$localePrefix.'$1"', (string) $body);
                }
            @endphp
            {!! $body !!}
            @php
                $telegram = cms_theme('telegram');
                $email = cms_theme('email');
                $telegramHandle = '';
                if (preg_match('~(?:t\.me/|@)([A-Za-z0-9_]{3,})~', $telegram, $telegramMatch)) {
                    $telegramHandle = '@'.$telegramMatch[1];
                }
            @endphp
            @if($telegram !== '')
                <p><a class="ig-btn" href="{{ $telegram }}" target="_blank" rel="noopener">{{ $telegramHandle !== '' ? 'Telegram '.$telegramHandle : 'Telegram' }}</a></p>
            @endif
            @if($email !== '')
                <p><a href="mailto:{{ $email }}">{{ $email }}</a></p>
            @endif
        </div>
        <div class="ig-form">
            @cmsForm(['slug' => 'gw-inquiry', 'captcha' => true, 'submit' => cms_t('提交', 'Send')])
        </div>
    </div>
@endsection
