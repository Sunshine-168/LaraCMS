    <h1>{{ cms_ui('reset_password') }}</h1>
    @if($errors->any())<p style="color:#c00">{{ $errors->first() }}</p>@endif
    <form method="post" action="{{ cms_route('member.reset.submit') }}" class="card-soft" style="max-width:480px;margin-top:16px">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <p><label>{{ cms_ui('new_password') }}<br><input type="password" name="password" required minlength="10" autocomplete="new-password" style="width:100%;padding:8px"></label></p>
        <p><label>{{ cms_ui('password_confirm') }}<br><input type="password" name="password_confirmation" required autocomplete="new-password" style="width:100%;padding:8px"></label></p>
        <p><button class="btn" type="submit">{{ cms_ui('save') }}</button></p>
    </form>
