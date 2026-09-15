@extends($layout ?? 'errors.bare')

@section('title', cms_ui('not_found'))

@section('content')
    <div class="card-soft" style="margin:48px auto;max-width:520px;text-align:center">
        <p class="muted">404</p>
        <h1>{{ cms_ui('not_found') }}</h1>
        <p class="muted">{{ cms_ui('not_found_hint') }}</p>
        <p><a class="btn" href="{{ url('/') }}">{{ cms_ui('back_home') }}</a></p>
    </div>
@endsection
