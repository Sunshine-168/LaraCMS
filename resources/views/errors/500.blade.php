@extends('errors.bare')

@section('title', cms_ui('server_error'))

@section('content')
    <p>500</p>
    <h1>{{ cms_ui('server_error') }}</h1>
    <p>{{ $siteName ?? config('app.name') }}</p>
    <p>{{ cms_ui('server_error_hint') }}</p>
    <p><a class="btn" href="{{ cms_front_url() }}">{{ cms_ui('back_home') }}</a></p>
@endsection
