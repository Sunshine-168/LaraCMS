    <h1>{{ cms_ui('member_login') }}</h1>
    @if(session('error'))<p style="color:#b91c1c">{{ session('error') }}</p>@endif
    <form method="post" action="{{ cms_route('member.login.submit') }}" class="card-soft" style="max-width:420px;margin-top:16px">
        @csrf
        <label>{{ cms_ui('email') }}</label>
        <input type="email" name="email" value="{{ old('email') }}" required autocomplete="username" style="width:100%;padding:8px;margin:6px 0 12px">
        <label>{{ cms_ui('password') }}</label>
        <input type="password" name="password" required autocomplete="current-password" style="width:100%;padding:8px;margin:6px 0 12px">
        <label><input type="checkbox" name="remember" value="1"> {{ cms_ui('remember') }}</label>
        @include('cms.partials.auth-captcha')
        @error('email')<p style="color:#b91c1c">{{ $message }}</p>@enderror
        <p><button class="btn" type="submit">{{ cms_ui('login') }}</button>
            <a href="{{ cms_route('member.register') }}">{{ cms_ui('register_account') }}</a>
            <a href="{{ cms_route('member.forgot') }}">{{ cms_ui('forgot_password') }}</a></p>
    </form>
    <p class="muted">{{ cms_ui('demo_login_hint') }}</p>
