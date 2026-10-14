@extends('themes.bc.partials.layout')

@section('content')
    <header class="ig-pagehead">
        <div class="ig-wrap">
            @cmsBreadcrumb
            <h1>{{ $entry->t('title') }}</h1>
        </div>
    </header>
    <div class="ig-wrap bc-page ig-contact">
        <div class="bc-prose">
            {!! $entry->linked_content ?? $entry->t('content') !!}
            @php $telegram = cms_theme('telegram'); $email = cms_theme('email'); @endphp
            @if($telegram !== '')
                <p><a class="ig-btn" href="{{ $telegram }}" target="_blank" rel="noopener">Telegram</a></p>
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
