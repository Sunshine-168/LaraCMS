    <h1>会员注册</h1>
    @if($errors->any())
        <p style="color:#c00">{{ $errors->first() }}</p>
    @endif
    <form method="post" action="{{ route('member.register.submit') }}" class="card-soft" style="max-width:480px;margin-top:16px">
        @csrf
        <p><label>昵称<br><input type="text" name="name" value="{{ old('name') }}" required style="width:100%;padding:8px"></label></p>
        <p><label>邮箱<br><input type="email" name="email" value="{{ old('email') }}" required style="width:100%;padding:8px"></label></p>
        <p><label>密码<br><input type="password" name="password" required minlength="6" style="width:100%;padding:8px"></label></p>
        <p><label>确认密码<br><input type="password" name="password_confirmation" required style="width:100%;padding:8px"></label></p>
        @include('cms.partials.auth-captcha')
        <p><button class="btn" type="submit">注册</button>
            <a href="{{ route('member.login') }}">已有账号？登录</a></p>
    </form>
