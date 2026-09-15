@extends($layout ?? 'errors.bare')

@section('title', cms_ui('forbidden'))

@section('content')
    <div class="card-soft" style="margin:48px auto;max-width:520px;text-align:center">
        <p class="muted">403</p>
        <h1>{{ cms_ui('forbidden') }}</h1>
        <p class="muted">{{ cms_ui('forbidden_hint') }}</p>
        <p><a class="btn" href="{{ cms_front_url() }}">{{ cms_ui('back_home') }}</a></p>
    </div>
@endsection
