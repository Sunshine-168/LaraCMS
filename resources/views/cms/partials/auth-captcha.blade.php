@if(\App\Support\Captcha::enabled())
    @php $cap = \App\Support\Captcha::generate(); @endphp
    <p><label>{{ cms_ui('captcha') }} {{ $cap['question'] }}<br>
        <input type="text" name="captcha" required inputmode="numeric" autocomplete="off" style="width:100%;padding:8px;margin:6px 0 12px">
    </label></p>
    @error('captcha')<p style="color:#b91c1c">{{ $message }}</p>@enderror
@endif
