    <h1>{{ cms_ui('forgot_title') }}</h1>
    @if(session('status'))<p style="color:green">{{ session('status') }}</p>@endif
    @if(session('reset_url'))
        <p class="muted">本地调试链接：</p>
        <p><a href="{{ session('reset_url') }}">{{ session('reset_url') }}</a></p>
    @endif
    @if($errors->any())<p style="color:#c00">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ cms_route('member.forgot.submit') }}" class="card-soft" style="max-width:480px;margin-top:16px">
        @csrf
        <p><label>{{ cms_ui('email') }}<br><input type="email" name="email" value="{{ old('email') }}" required style="width:100%;padding:8px"></label></p>
        <p><button class="btn" type="submit">{{ cms_ui('forgot_send') }}</button>
            <a href="{{ cms_front_url('member/login') }}">{{ cms_ui('back_login') }}</a></p>
    </form>
