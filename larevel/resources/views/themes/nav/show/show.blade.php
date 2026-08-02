@extends('themes.nav.partials.layout')
@section('content')
    <div class="link" style="max-width:420px;margin:40px auto">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">{{ $entry->t('excerpt') }}</p>
        <p><a href="{{ route('go', $entry) }}" target="_blank">访问网站</a></p>
    </div>
@endsection
