<p class="login-links ui-switch-login">
    @foreach(\App\Support\AdminUi::options() as $code => $label)
        <a href="{{ request()->fullUrlWithQuery(['ui' => $code]) }}" class="{{ \App\Support\AdminUi::current() === $code ? 'is-on' : '' }}">{{ $label }}</a>
    @endforeach
</p>
