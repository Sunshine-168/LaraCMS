@php
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
@endphp
<p class="muted">主题是一套前台外观（Blade 文件 + 它声明的模型）。没有应用市场，都在本机 {!! $hl('themes', '主题') !!} 页切换。改文件、标签见「<a href="{{ $urls['theme'] ?? '#' }}">模板</a>」。</p>

<div class="help-jumps">
    @if(!empty($urls['themes']))
        <a href="{{ $urls['themes'] }}">打开主题页</a>
    @endif
    @if(!empty($urls['cache']))
        <a href="{{ $urls['cache'] }}">清缓存</a>
    @endif
    <a href="{{ $urls['theme'] ?? '#' }}">改模板文件</a>
    <a href="{{ $urls['front'] ?? url('/') }}" target="_blank" rel="noopener">看前台</a>
</div>

<h3>怎么换到本站</h3>
<ol class="help-steps">
    <li>打开{!! $hl('themes', '主题') !!}。卡片上「当前使用」就是正在用的。点「配置」进这一套的多语言、模型和自定义参数。</li>
    <li>点预览图看长什么样。缺首页的不能用于本站（没有 <code>index/index.blade.php</code>）。</li>
    <li>点「用于本站」：会把这套外观设为当前主题，并<strong>补上缺少的内容模型</strong>和<strong>空栏目</strong>。已有模型字段、已有栏目和文章都不会改、不会删。</li>
    <li>当前这套还缺模型时，按钮是「补上模型」，只补缺的，不换外观。</li>
    <li>前台还是旧样子，到{!! $hl('cache', '缓存') !!}清空。</li>
</ol>
<p class="hint">空栏目补上后还没有文章，需要自己写。体育主题只作公开资讯/目录演示，请合法合规使用。</p>

<h3>怎么加一套</h3>
<table class="help-doc">
    <thead>
        <tr><th>做法</th><th>说明</th></tr>
    </thead>
    <tbody>
        <tr><td>复制一套</td><td>从现有完整主题拷文件夹。只改新目录，自带主题不会被覆盖。</td></tr>
        <tr><td>上传 zip</td><td>包里要有 <code>index/index.blade.php</code>，可带一层目录名当标识。不要带可执行 PHP。</td></tr>
        <tr><td>丢文件夹</td><td>放到 <code>resources/views/themes/标识/</code>，刷新主题页即出现。</td></tr>
        <tr><td>命令行</td><td><code>php artisan cms:theme-make brand --from=enterprise --name=品牌站</code></td></tr>
    </tbody>
</table>
<p class="muted">自带主题不能删。自己加的可以「去掉」（会删文件夹），正在用的不能去掉。标识只能小写字母数字和横线。</p>

<h3>自带主题干什么用</h3>
<table class="help-doc">
    <thead>
        <tr><th>主题</th><th>适合</th><th>会用到的模型</th></tr>
    </thead>
    <tbody>
        <tr><td>企业</td><td>公司官网</td><td>文章、单页、产品、招聘</td></tr>
        <tr><td>博客</td><td>简单博客</td><td>文章</td></tr>
        <tr><td>下载</td><td>资源下载</td><td>下载</td></tr>
        <tr><td>导航</td><td>网址导航</td><td>链接</td></tr>
        <tr><td>源码</td><td>源码站</td><td>源码</td></tr>
        <tr><td>漫画</td><td>漫画连载</td><td>漫画、章节</td></tr>
        <tr><td>体育</td><td>资讯/信号目录</td><td>信号</td></tr>
        <tr><td>移动</td><td>手机皮肤</td><td>与企业类似，可作手机外观</td></tr>
    </tbody>
</table>
<p class="muted">点「用于本站」时按 <code>theme.json</code> 里的 <code>models</code> / <code>categories</code> 补缺（配置页勾选的模型会覆盖 json）。栏目标题可写成 <code>{"zh_cn":"新闻中心","en":"News"}</code>。卡片上「多语言」「自适应」也可在配置页勾选。自定义参数写在 <code>config</code>，前台 <code>cms_theme('键')</code>。</p>

<h3>站名、Logo、手机外观</h3>
<p class="muted">{!! $hl('settings', '设置') !!}里改网站名称。{!! $hl('settingsLook', '设置 → 外观') !!}可上传 Logo / Favicon、主色、页脚、ICP。留空则用主题自带配色。</p>
<p class="muted">手机要另一套皮：在设置「更多」里选「手机外观」（常用 <code>mobile</code>），可再填手机域名，或按手机浏览器自动切。</p>

<h3>主题相关问题</h3>
<div class="help-faq">
    <details>
        <summary>用于本站会不会清掉现在的文章？</summary>
        <p>不会。只补<strong>没有的</strong>模型和空栏目。已有字段、栏目、文章原样保留。换皮后字段对不上的，前台可能显示不全，需要自己改栏目模型或模板。</p>
    </details>
    <details>
        <summary>按钮是灰的 / 显示缺首页</summary>
        <p>这套没有 <code>index/index.blade.php</code>。复制或上传时包不完整就会这样。自带主题不应出现。</p>
    </details>
    <details>
        <summary>换了主题前台没变</summary>
        <p>到{!! $hl('cache', '缓存') !!}清空。磁盘静态页若开着，也要重新生成。</p>
    </details>
    <details>
        <summary>复制后改哪</summary>
        <p>只改新文件夹里的 Blade / CSS / <code>theme.json</code>。改完刷新主题页。预览图放 <code>preview.png</code>。标签怎么写见「<a href="{{ $urls['tags'] ?? '#' }}">标签</a>」。</p>
    </details>
</div>
