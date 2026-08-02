@extends('themes.sports.partials.layout')
@section('content')
    @cmsBreadcrumb
    <div class="card">
        <h1>{{ $entry->t('title') }}</h1>
        <p class="muted">{{ $entry->attr('sport') }} · {{ $entry->attr('kickoff') }} · {{ $entry->attr('quality') }}</p>
        <div>{!! nl2br(e($entry->t('content'))) !!}</div>
        <p style="margin-top:16px"><a class="btn" href="{{ route('go', $entry) }}" target="_blank">打开信号/资讯源</a></p>
    </div>
@endsection
