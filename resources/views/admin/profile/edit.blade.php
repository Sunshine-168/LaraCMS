@extends('admin.layout')
@section('title', admin_t('top.password'))
@section('icon', 'user')
@section('content')
    <div class="settings-page profile-page">
        <p class="muted recycle-lead">改自己在后台显示的名字和登录密码。给别人开账号请走「管理员」。</p>

        @if($errors->any())
            <div class="flash error">{{ $errors->first() }}</div>
        @endif

        <form method="post" action="{{ route('admin.profile.update') }}">
            @csrf
            @method('PUT')
            <label for="profile-name">名称</label>
            <input id="profile-name" type="text" name="name" value="{{ old('name', $user->name) }}" required maxlength="100" autocomplete="name">
            <p class="muted field-hint">顶栏和日志里看到的名字。</p>

            <label>登录邮箱</label>
            <input type="email" value="{{ $user->email }}" disabled>
            <p class="muted field-hint">用来登录后台，不能在这里改。</p>

            <label>{{ admin_t('locale.admin_mine') }}</label>
            <div class="locale-ui-choices">
                @foreach(\App\Support\AdminUi::options() as $code => $label)
                    <label class="inline">
                        <input type="radio" name="ui_locale" value="{{ $code }}" @checked(old('ui_locale', $user->ui_locale ?: \App\Support\AdminUi::current()) === $code)>
                        {{ $label }}
                    </label>
                @endforeach
            </div>
            <p class="muted field-hint">{{ admin_t('locale.admin_note') }}</p>

            <div class="form-actions">
                <button class="btn" type="submit">保存名称</button>
            </div>
        </form>

        <h3>登录密码</h3>
        <form method="post" action="{{ route('admin.profile.password') }}">
            @csrf
            @method('PUT')
            <label for="profile-current">当前密码</label>
            <input id="profile-current" type="password" name="current_password" required autocomplete="current-password">

            <label for="profile-password">新密码</label>
            <input id="profile-password" type="password" name="password" required minlength="8" autocomplete="new-password">
            <p class="muted field-hint">至少 8 位。</p>

            <label for="profile-password-confirm">再输一次新密码</label>
            <input id="profile-password-confirm" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">

            <div class="form-actions">
                <button class="btn" type="submit">更新密码</button>
            </div>
        </form>
    </div>
@endsection
