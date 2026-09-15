@extends('themes.nav.partials.layout')
@section('content')
    <div class="link" style="max-width:420px;margin:40px auto">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">{{ $entry->t('excerpt') }}</p>
        <p><a href="{{ cms_route('go', $entry) }}" target="_blank">{{ cms_ui('visit_site') }}</a></p>
        @include('cms.partials.entry-tags')
    </div>

    @include('cms.comments')
@endsection
