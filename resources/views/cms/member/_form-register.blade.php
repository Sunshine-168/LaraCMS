    <h1>{{ cms_ui('member_register') }}</h1>
    @if($errors->any())
        <p style="color:#c00">{{ $errors->first() }}</p>
    @endif
    <form method="post" action="{{ cms_route('member.register.submit') }}" class="card-soft" style="max-width:480px;margin-top:16px">
        @csrf
        <p><label>{{ cms_ui('nickname') }}<br><input type="text" name="name" value="{{ old('name') }}" required style="width:100%;padding:8px"></label></p>
        <p><label>{{ cms_ui('email') }}<br><input type="email" name="email" value="{{ old('email') }}" required style="width:100%;padding:8px"></label></p>
        <p><label>{{ cms_ui('password') }}<br><input type="password" name="password" required minlength="10" style="width:100%;padding:8px"></label></p>
        <p><label>{{ cms_ui('password_confirm') }}<br><input type="password" name="password_confirmation" required style="width:100%;padding:8px"></label></p>
        @include('cms.partials.auth-captcha')
        <p><button class="btn" type="submit">{{ cms_ui('register') }}</button>
            <a href="{{ cms_front_url('member/login') }}">{{ cms_ui('have_account') }}</a></p>
    </form>
