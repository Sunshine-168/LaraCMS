@extends($layout ?? 'errors.bare')

@section('title', cms_ui('session_expired'))

@section('content')
    <div class="card-soft" style="margin:48px auto;max-width:520px;text-align:center">
        <p class="muted">419</p>
        <h1>{{ cms_ui('session_expired') }}</h1>
        <p class="muted">{{ cms_ui('session_expired_hint') }}</p>
        <p>
            <a class="btn" href="{{ url()->previous() }}">{{ cms_ui('try_again') }}</a>
            <a href="{{ cms_front_url() }}">{{ cms_ui('back_home') }}</a>
        </p>
    </div>
@endsection
