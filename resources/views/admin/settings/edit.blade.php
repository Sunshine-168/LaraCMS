@extends('admin.layout')
@section('title', admin_t('nav.settings'))
@section('icon', 'cog')
@php
    $s = $settings;
    $tab = $tab ?? 'site';
    $tabs = [
        'site' => '网站',
        'look' => '外观',
        'interact' => '评论',
        'more' => '更多',
    ];
    $logoUrl = ! empty($s['logo']) ? \App\Support\ThemeAppearance::mediaUrl($s['logo']) : '';
    $faviconUrl = ! empty($s['favicon']) ? \App\Support\ThemeAppearance::mediaUrl($s['favicon']) : '';
    $wm = $s['upload_watermark_image'] ?? '';
    $wmUrl = $wm !== '' ? cms_media(\Illuminate\Support\Facades\Storage::disk('public')->url($wm)) : '';
@endphp
@section('content')
    <p class="muted recycle-lead">改网站名字、Logo 和评论规则。不常动的项在「更多」里。</p>

    <form method="post" class="settings-page" id="settingsForm" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="tab" id="settingsTab" value="{{ $tab }}">

        <div class="tabs settings-tabs" id="settingsTabs">
            @foreach($tabs as $key => $label)
                <button type="button" class="{{ $tab === $key ? 'active' : '' }}" data-tab="{{ $key }}">{{ $label }}</button>
            @endforeach
        </div>

        <div class="settings-pane{{ $tab === 'site' ? ' active' : '' }}" data-pane="site">
            <label for="site_name">网站名称</label>
            <input id="site_name" type="text" name="site_name" value="{{ old('site_name', $s['site_name'] ?? '') }}" placeholder="会出现在浏览器标题和页脚">

            <label for="site_description">一句话介绍</label>
            <textarea id="site_description" name="site_description" rows="3">{{ old('site_description', $s['site_description'] ?? '') }}</textarea>
            <p class="muted field-hint">搜索引擎和分享卡片会用到。尽量写清网站是做什么的。</p>

            <label for="site_keywords">搜索关键词</label>
            <input id="site_keywords" type="text" name="site_keywords" value="{{ old('site_keywords', $s['site_keywords'] ?? '') }}" placeholder="用逗号分开，如 新闻,CMS">
            <p class="muted field-hint">给搜索引擎看，前台访客一般看不到。</p>

            <label for="theme">外观主题</label>
            <select id="theme" name="theme">
                @foreach(app(\App\Services\Cms\ThemeCatalog::class)->all() as $themeItem)
                    @if($themeItem['complete'])
                    <option value="{{ $themeItem['slug'] }}" @selected(old('theme', $s['theme'] ?? config('cms.theme')) === $themeItem['slug'])>{{ $themeItem['name'] }}</option>
                    @endif
                @endforeach
            </select>
            <p class="muted field-hint">决定首页长什么样。<a href="{{ route('admin.themes.index') }}">复制或添加主题</a>。</p>

            <label class="inline">
                <input type="checkbox" name="site_closed" value="1" @checked(old('site_closed', $s['site_closed'] ?? '0') == '1')>
                暂时关闭网站
            </label>
            <label for="site_closed_message">关站时访客看到的说明</label>
            <input id="site_closed_message" type="text" name="site_closed_message" value="{{ old('site_closed_message', $s['site_closed_message'] ?? '站点维护中，请稍后再访。') }}">
        </div>

        <div class="settings-pane{{ $tab === 'look' ? ' active' : '' }}" data-pane="look">
            <p class="muted field-hint">改 Logo、颜色和页脚，不用改模板。首页标题留空则用主题自带文案。</p>

            <label>网站 Logo</label>
            @include('admin.settings._file-preview', [
                'url' => $logoUrl,
                'name' => 'logo_file',
                'empty' => '还没有 Logo',
                'clear' => 'logo_clear',
                'kind' => 'logo',
            ])

            <label>浏览器小图标</label>
            @include('admin.settings._file-preview', [
                'url' => $faviconUrl,
                'name' => 'favicon_file',
                'empty' => '还没有小图标',
                'clear' => 'favicon_clear',
                'kind' => 'favicon',
                'accept' => 'image/png,image/jpeg,image/webp,image/gif,image/x-icon',
            ])
            <p class="muted field-hint">出现在浏览器标签上，建议用正方形小图。</p>

            <label for="primary_color">主色</label>
            <input id="primary_color" type="text" name="primary_color" value="{{ old('primary_color', $s['primary_color'] ?? '') }}" placeholder="#1b4f72" maxlength="7">
            <p class="muted field-hint">如 #1b4f72。留空则用主题自带配色。</p>

            <label for="hero_title">首页大标题</label>
            <input id="hero_title" type="text" name="hero_title" value="{{ old('hero_title', $s['hero_title'] ?? '') }}" maxlength="120">
            <label for="hero_subtitle">首页副标题</label>
            <input id="hero_subtitle" type="text" name="hero_subtitle" value="{{ old('hero_subtitle', $s['hero_subtitle'] ?? '') }}" maxlength="200">
            <p class="muted field-hint">有推荐位大图时，大图优先于这两行字。</p>

            <label for="footer_text">页脚补充</label>
            <input id="footer_text" type="text" name="footer_text" value="{{ old('footer_text', $s['footer_text'] ?? '') }}" maxlength="500" placeholder="地址、电话或版权说明">

            <label for="icp">备案号</label>
            <input id="icp" type="text" name="icp" value="{{ old('icp', $s['icp'] ?? '') }}" maxlength="80" placeholder="京ICP备00000000号-1">
        </div>

        <div class="settings-pane{{ $tab === 'interact' ? ' active' : '' }}" data-pane="interact">
            <label class="inline">
                <input type="checkbox" name="comments_enabled" value="1" @checked(old('comments_enabled', $s['comments_enabled'] ?? '1') == '1')>
                允许在文章下留言
            </label>
            <label class="inline">
                <input type="checkbox" name="comments_guest" value="1" @checked(old('comments_guest', $s['comments_guest'] ?? '1') == '1')>
                没登录也能留
            </label>
            <label class="inline">
                <input type="checkbox" name="comments_auto_approve" value="1" @checked(old('comments_auto_approve', $s['comments_auto_approve'] ?? '0') == '1')>
                会员留言不用审（游客仍要审）
            </label>

            <label for="comments_notify_email">运营通知邮箱</label>
            <input id="comments_notify_email" type="email" name="comments_notify_email" value="{{ old('comments_notify_email', $s['comments_notify_email'] ?? '') }}" placeholder="ops@example.com">
            <p class="muted field-hint">新评论、会员待审稿会发到这里。表单另有各自的通知邮箱。留空则不发。</p>

            <label for="comments_rate_seconds">同一来源最短间隔（秒）</label>
            <input id="comments_rate_seconds" type="number" name="comments_rate_seconds" min="10" max="3600" value="{{ old('comments_rate_seconds', $s['comments_rate_seconds'] ?? 60) }}">
            <p class="muted field-hint">防止刷屏。建议 60 秒。</p>
        </div>

        <div class="settings-pane{{ $tab === 'more' ? ' active' : '' }}" data-pane="more">
            <p class="muted field-hint">一般不用天天改。改文章链接方式后，旧地址可能打不开。</p>

            <h3>文章地址</h3>
            <label for="url_mode">文章链接怎么写</label>
            <select id="url_mode" name="url_mode">
                @foreach(\App\Support\UrlMode::options() as $mode => $label)
                    <option value="{{ $mode }}" @selected(old('url_mode', $s['url_mode'] ?? 'cat_entry') === $mode)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="muted field-hint">栏目页仍是「栏目名」。这里只影响单篇文章的地址。</p>

            <h3>手机访问</h3>
            <label for="mobile_theme">手机外观</label>
            <select id="mobile_theme" name="mobile_theme">
                <option value="">跟电脑一样</option>
                @foreach(app(\App\Services\Cms\ThemeCatalog::class)->all() as $themeItem)
                    @if($themeItem['complete'])
                        <option value="{{ $themeItem['slug'] }}" @selected(old('mobile_theme', $s['mobile_theme'] ?? '') === $themeItem['slug'])>{{ $themeItem['name'] }}</option>
                    @endif
                @endforeach
            </select>
            <label for="mobile_domain">手机专用域名</label>
            <input id="mobile_domain" type="text" name="mobile_domain" value="{{ old('mobile_domain', $s['mobile_domain'] ?? '') }}" placeholder="m.example.com">
            <p class="muted field-hint">多个域名用逗号分开。留空则只按是否手机浏览器来判断。</p>
            <label for="mobile_detect">怎么判断是手机</label>
            <select id="mobile_detect" name="mobile_detect">
                @foreach(['both' => '手机域名或手机浏览器', 'domain' => '只认手机域名', 'ua' => '只认手机浏览器'] as $k => $label)
                    <option value="{{ $k }}" @selected(old('mobile_detect', $s['mobile_detect'] ?? 'both') === $k)>{{ $label }}</option>
                @endforeach
            </select>

            <h3>图片上传</h3>
            <label for="upload_thumb_width">大图缩小到多宽</label>
            <input id="upload_thumb_width" type="number" name="upload_thumb_width" min="0" max="5000" value="{{ old('upload_thumb_width', $s['upload_thumb_width'] ?? 0) }}">
            <p class="muted field-hint">单位像素。填 0 表示不缩小。</p>
            <label for="upload_watermark_text">文字水印</label>
            <input id="upload_watermark_text" type="text" name="upload_watermark_text" value="{{ old('upload_watermark_text', $s['upload_watermark_text'] ?? '') }}" placeholder="© 本站">
            <p class="muted field-hint">留空则不加文字。中文显示不正常时，改用下面的图片水印。</p>
            <label for="upload_watermark_position">水印位置</label>
            <select id="upload_watermark_position" name="upload_watermark_position">
                @foreach(['br' => '右下', 'bl' => '左下', 'tr' => '右上', 'tl' => '左上', 'center' => '居中'] as $k => $label)
                    <option value="{{ $k }}" @selected(old('upload_watermark_position', $s['upload_watermark_position'] ?? 'br') === $k)>{{ $label }}</option>
                @endforeach
            </select>
            <label>图片水印</label>
            @include('admin.settings._file-preview', [
                'url' => $wmUrl,
                'name' => 'watermark_image',
                'empty' => '还没有水印图',
                'clear' => 'upload_watermark_image_clear',
                'kind' => 'watermark',
            ])

            <h3>发信</h3>
            <p class="muted field-hint">留言通知、找回密码、待审稿会用到。没配服务器时邮件进日志，前台不会露出重置链接（仅本地调试模式会显示）。密码留空表示不改已保存的值。</p>
            <label for="mail_host">邮件服务器</label>
            <input id="mail_host" type="text" name="mail_host" value="{{ old('mail_host', $s['mail_host'] ?? '') }}" placeholder="smtp.example.com">
            <div class="settings-two">
                <div>
                    <label for="mail_port">端口</label>
                    <input id="mail_port" type="number" name="mail_port" value="{{ old('mail_port', $s['mail_port'] ?? 587) }}">
                </div>
                <div>
                    <label for="mail_encryption">传输加密</label>
                    <select id="mail_encryption" name="mail_encryption">
                        @foreach(['tls' => 'TLS（常用）', 'ssl' => 'SSL', '' => '不加密'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('mail_encryption', $s['mail_encryption'] ?? 'tls') === (string) $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <label for="mail_username">登录账号</label>
            <input id="mail_username" type="text" name="mail_username" value="{{ old('mail_username', $s['mail_username'] ?? '') }}">
            <label for="mail_password">密码</label>
            <input id="mail_password" type="password" name="mail_password" value="" autocomplete="new-password" placeholder="{{ ! empty($s['mail_password']) ? '已保存，留空不改' : '' }}">
            <label for="mail_from_address">发件人邮箱</label>
            <input id="mail_from_address" type="email" name="mail_from_address" value="{{ old('mail_from_address', $s['mail_from_address'] ?? '') }}">
            <label for="mail_from_name">发件人名称</label>
            <input id="mail_from_name" type="text" name="mail_from_name" value="{{ old('mail_from_name', $s['mail_from_name'] ?? '') }}">

            <h3>加速与接口</h3>
            <label for="cdn_url">图片走这个域名</label>
            <input id="cdn_url" type="url" name="cdn_url" value="{{ old('cdn_url', $s['cdn_url'] ?? '') }}" placeholder="https://cdn.example.com">
            <p class="muted field-hint">附件会改到这个域名。留空则用本站地址。不要填后台地址。</p>
            <label class="inline">
                <input type="checkbox" name="api_enable" value="1" @checked(old('api_enable', $s['api_enable'] ?? '0') == '1')>
                读内容需要密钥
            </label>
            <label for="api_token">接口密钥</label>
            <input id="api_token" type="text" name="api_token" value="{{ old('api_token', $s['api_token'] ?? '') }}" placeholder="一串不容易猜的字符">
            <p class="muted field-hint">勾选「需要密钥」时必填。关掉则谁都能读公开内容。</p>
            <details class="settings-details">
                <summary>有哪些接口</summary>
                <p class="muted field-hint">读：站点配置、栏目、文章、搜索、标签、推荐位。写：表单提交、评论（带密钥可免验证码）。地址在网站根路径下的 /api。</p>
            </details>

            <h3>搜索引擎</h3>
            <label for="robots_extra">robots.txt 补充规则</label>
            <textarea id="robots_extra" name="robots_extra" rows="5" placeholder="Disallow: /private">{{ old('robots_extra', $s['robots_extra'] ?? '') }}</textarea>
            <p class="muted field-hint">写在默认规则后面。关站时整站仍是 Disallow: /。后台、会员、安装、预览已经禁止抓取。前台地址 /robots.txt。收录范围在侧栏「运营 → 更多 → 地图」（站点地图）@if(auth('admin')->user()?->canPerm('sitemap'))（<a href="{{ route('admin.sitemap.edit') }}">打开</a>）@endif。</p>

            <h3>统计与内链</h3>
            <label for="analytics_code">统计代码</label>
            <textarea id="analytics_code" name="analytics_code" rows="5" placeholder="把统计平台给的代码贴在这里">{{ old('analytics_code', $s['analytics_code'] ?? '') }}</textarea>
            <p class="muted field-hint">会插到前台页面底部，如百度统计、Google Analytics。</p>
            <label class="inline">
                <input type="checkbox" name="tag_autolink" value="1" @checked(old('tag_autolink', $s['tag_autolink'] ?? '1') != '0')>
                正文里自动给标签加链接
            </label>
            <p class="muted field-hint">文章里出现标签名时，点过去会打开标签页。</p>

            <details class="settings-details">
                <summary>后台选点位置</summary>
                <p class="muted field-hint">填内容选地点时，地图打开的默认位置。一般不用改。</p>
                <div class="settings-two">
                    <div>
                        <label for="map_default_lng">经度</label>
                        <input id="map_default_lng" type="text" name="map_default_lng" value="{{ old('map_default_lng', $s['map_default_lng'] ?? '116.397') }}">
                    </div>
                    <div>
                        <label for="map_default_lat">纬度</label>
                        <input id="map_default_lat" type="text" name="map_default_lat" value="{{ old('map_default_lat', $s['map_default_lat'] ?? '39.908') }}">
                    </div>
                </div>
            </details>
        </div>

        <div class="form-actions settings-save">
            <button class="btn" type="submit">保存设置</button>
        </div>
    </form>
    <div class="settings-pane settings-maintain{{ $tab === 'more' ? ' active' : '' }}" data-pane="more">
        <p class="muted field-hint">页面和模板缓存请到「<a href="{{ route('admin.html-cache.edit') }}">缓存</a>」里清。这里只做备份和演示数据。</p>
        @include('admin.settings._maintain')
    </div>
@endsection
@push('scripts')
<script>
(function () {
    var tabInput = document.getElementById('settingsTab');
    var tabs = document.getElementById('settingsTabs');
    if (tabInput && tabs) {
        tabs.querySelectorAll('[data-tab]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tab = btn.getAttribute('data-tab');
                tabInput.value = tab;
                tabs.querySelectorAll('[data-tab]').forEach(function (item) {
                    item.classList.toggle('active', item === btn);
                });
                document.querySelectorAll('.settings-pane').forEach(function (pane) {
                    pane.classList.toggle('active', pane.getAttribute('data-pane') === tab);
                });
                var url = new URL(window.location.href);
                if (tab === 'site') url.searchParams.delete('tab');
                else url.searchParams.set('tab', tab);
                history.replaceState(null, '', url);
            });
        });
    }

    document.querySelectorAll('[data-file-preview]').forEach(function (root) {
        var input = root.querySelector('[data-file-input]');
        var img = root.querySelector('img');
        var empty = root.querySelector('.settings-file-empty');
        var thumb = root.querySelector('.settings-file-thumb');
        var clear = root.querySelector('[data-file-clear]');
        if (!input || !img || !empty || !thumb) return;
        var objectUrl = '';
        var saved = (img.getAttribute('data-saved-src') || '').trim();
        var show = function (url) {
            if (objectUrl && objectUrl !== url) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = '';
            }
            if (!url) {
                img.removeAttribute('src');
                img.hidden = true;
                empty.hidden = false;
                thumb.classList.add('is-empty');
                thumb.style.backgroundImage = 'none';
                return;
            }
            img.hidden = false;
            empty.hidden = true;
            thumb.classList.remove('is-empty');
            thumb.style.backgroundImage = 'url("' + String(url).replace(/"/g, '\\"') + '")';
            if (img.getAttribute('src') !== url) {
                img.src = url;
            }
        };
        input.addEventListener('change', function () {
            var file = input.files && input.files[0];
            if (clear) clear.checked = false;
            if (!file) {
                show(saved);
                return;
            }
            objectUrl = URL.createObjectURL(file);
            show(objectUrl);
        });
        if (clear) {
            clear.addEventListener('change', function () {
                if (clear.checked) {
                    input.value = '';
                    show('');
                } else {
                    show(saved);
                }
            });
        }
    });
})();
</script>
@endpush
