@extends('admin.layout')
@section('title', '站点设置')
@section('icon', 'cog')
@section('content')
    <form method="post" action="{{ route('admin.settings.update') }}">
        @csrf
        @method('PUT')

        <h3>基本信息</h3>
        <label>站点名称</label>
        <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name'] ?? '') }}">
        <label>站点描述</label>
        <textarea name="site_description">{{ old('site_description', $settings['site_description'] ?? '') }}</textarea>
        <label>关键词</label>
        <input type="text" name="site_keywords" value="{{ old('site_keywords', $settings['site_keywords'] ?? '') }}">
        <label>主题</label>
        <select name="theme">
            @foreach(config('cms.themes', []) as $theme)
                <option value="{{ $theme }}" @selected(old('theme', $settings['theme'] ?? config('cms.theme')) === $theme)>{{ $theme }}</option>
            @endforeach
        </select>

        <h3 style="margin-top:22px">站点状态</h3>
        <label class="inline"><input type="checkbox" name="site_closed" value="1" @checked(old('site_closed', $settings['site_closed'] ?? '0') == '1')> 关闭前台（维护模式）</label>
        <label>关站提示文案</label>
        <input type="text" name="site_closed_message" value="{{ old('site_closed_message', $settings['site_closed_message'] ?? '站点维护中，请稍后再访。') }}">

        <h3 style="margin-top:22px">伪静态 URL</h3>
        <p class="muted">栏目页始终为 /栏目slug；文档链接模式可切换。</p>
        <label>文档 URL 模式</label>
        <select name="url_mode">
            @foreach(\App\Support\UrlMode::options() as $mode => $label)
                <option value="{{ $mode }}" @selected(old('url_mode', $settings['url_mode'] ?? 'cat_entry') === $mode)>{{ $label }}</option>
            @endforeach
        </select>

        <h3 style="margin-top:22px">REST API</h3>
        <label class="inline"><input type="checkbox" name="api_enable" value="1" @checked(old('api_enable', $settings['api_enable'] ?? '0') == '1')> 启用 API Token 校验（关闭则公开只读）</label>
        <label>API Token（启用校验时必填）</label>
        <input type="text" name="api_token" value="{{ old('api_token', $settings['api_token'] ?? '') }}" placeholder="随机长字符串">
        <p class="muted">接口前缀 /api：site/config、category/*、content/*、recommend?name=</p>

        <h3 style="margin-top:22px">移动端分流</h3>
        <p class="muted">配置移动主题后，按域名或 UA 自动切换主题（视图目录 themes/主题名）。</p>
        <label>移动主题（留空关闭分流）</label>
        <select name="mobile_theme">
            <option value="">不启用</option>
            @foreach(config('cms.themes', []) as $theme)
                <option value="{{ $theme }}" @selected(old('mobile_theme', $settings['mobile_theme'] ?? '') === $theme)>{{ $theme }}</option>
            @endforeach
        </select>
        <label>手机域名（逗号分隔，如 m.example.com）</label>
        <input type="text" name="mobile_domain" value="{{ old('mobile_domain', $settings['mobile_domain'] ?? '') }}" placeholder="m.example.com">
        <label>判定方式</label>
        <select name="mobile_detect">
            @foreach(['both'=>'域名或 UA','domain'=>'仅域名','ua'=>'仅 UA'] as $k => $label)
                <option value="{{ $k }}" @selected(old('mobile_detect', $settings['mobile_detect'] ?? 'both') === $k)>{{ $label }}</option>
            @endforeach
        </select>

        <h3 style="margin-top:22px">上传处理</h3>
        <p class="muted">上传图片时生效（需 PHP GD）。宽度为 0 表示不缩放。</p>
        <label>图片最大宽度（像素，超出等比缩小）</label>
        <input type="number" name="upload_thumb_width" min="0" max="5000" value="{{ old('upload_thumb_width', $settings['upload_thumb_width'] ?? 0) }}">
        <label>文字水印（留空关闭）</label>
        <input type="text" name="upload_watermark_text" value="{{ old('upload_watermark_text', $settings['upload_watermark_text'] ?? '') }}" placeholder="© MySite">

        <h3 style="margin-top:22px">内容 SEO</h3>
        <label class="inline"><input type="checkbox" name="tag_autolink" value="1" @checked(old('tag_autolink', $settings['tag_autolink'] ?? '1') != '0')> 标签自动内链（正文命中标签标题时链接到 /tag）</label>

        <h3 style="margin-top:22px">第三方统计代码</h3>
        <p class="muted">粘贴到前台 &lt;/body&gt; 前，如百度统计 / GA。</p>
        <textarea name="analytics_code" rows="5" placeholder="<script>...</script>">{{ old('analytics_code', $settings['analytics_code'] ?? '') }}</textarea>

        <h3 style="margin-top:22px">邮件（SMTP）</h3>
        <p class="muted">用于留言通知、找回密码等。密码留空表示不修改已保存值。</p>
        <label>SMTP Host</label>
        <input type="text" name="mail_host" value="{{ old('mail_host', $settings['mail_host'] ?? '') }}" placeholder="smtp.example.com">
        <label>端口</label>
        <input type="number" name="mail_port" value="{{ old('mail_port', $settings['mail_port'] ?? 587) }}">
        <label>加密</label>
        <select name="mail_encryption">
            @foreach(['tls'=>'TLS','ssl'=>'SSL',''=>'无'] as $val => $label)
                <option value="{{ $val }}" @selected(old('mail_encryption', $settings['mail_encryption'] ?? 'tls') === (string)$val)>{{ $label }}</option>
            @endforeach
        </select>
        <label>用户名</label>
        <input type="text" name="mail_username" value="{{ old('mail_username', $settings['mail_username'] ?? '') }}">
        <label>密码</label>
        <input type="password" name="mail_password" value="" autocomplete="new-password" placeholder="{{ !empty($settings['mail_password']) ? '已保存，留空不改' : '' }}">
        <label>发件人邮箱</label>
        <input type="email" name="mail_from_address" value="{{ old('mail_from_address', $settings['mail_from_address'] ?? '') }}">
        <label>发件人名称</label>
        <input type="text" name="mail_from_name" value="{{ old('mail_from_name', $settings['mail_from_name'] ?? '') }}">

        <div class="form-actions">
            <button class="btn" type="submit">保存</button>
        </div>
    </form>
@endsection
