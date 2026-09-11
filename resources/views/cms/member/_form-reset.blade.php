    <h1>设置新密码</h1>
    @if($errors->any())<p style="color:#c00">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ route('member.reset.submit') }}" class="card-soft" style="max-width:480px;margin-top:16px">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <p><label>新密码<br><input type="password" name="password" required minlength="6" style="width:100%;padding:8px"></label></p>
        <p><label>确认密码<br><input type="password" name="password_confirmation" required style="width:100%;padding:8px"></label></p>
        <p><button class="btn" type="submit">保存</button></p>
    </form>
