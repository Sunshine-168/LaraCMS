@php
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
@endphp
<p class="muted">前台长什么样由<strong>主题</strong>决定，栏目和文章由<strong>内容模型</strong>决定。侧栏每一项见「<a href="{{ $urls['helpAdmin'] ?? '#' }}">后台</a>」，换外观见「<a href="{{ $urls['helpThemes'] ?? '#' }}">主题</a>」，改 Blade 见「<a href="{{ $urls['theme'] ?? '#' }}">模板</a>」，取数据见「<a href="{{ $urls['tags'] ?? '#' }}">标签</a>」。</p>

<div class="help-jumps">
    @if(!empty($urls['settings']))
        <a href="{{ $urls['settings'] }}">改站名</a>
    @endif
    @if(!empty($urls['themes']))
        <a href="{{ $urls['themes'] }}">换主题</a>
    @endif
    @if(!empty($urls['categories']))
        <a href="{{ $urls['categories'] }}">建栏目</a>
    @endif
    @if(!empty($urls['entries']))
        <a href="{{ $urls['entries'] }}">写内容</a>
    @endif
    @if(!empty($urls['cache']))
        <a href="{{ $urls['cache'] }}">清缓存</a>
    @endif
    <a href="{{ $urls['front'] ?? url('/') }}" target="_blank" rel="noopener">看前台</a>
</div>

<h3>第一次建议按这个顺序</h3>
<ol class="help-steps">
    <li>{!! $hl('settings', '设置') !!} 里改网站名称、一句话介绍。</li>
    <li>{!! $hl('themes', '主题') !!} 选一套外观，点「用于本站」。缺的模型会自动补上，已有文章不会清掉。</li>
    <li>{!! $hl('categories', '栏目') !!} 建频道（新闻、产品、关于我们）。栏目要勾模型，列表页才知道用哪套字段。</li>
    <li>{!! $hl('entries', '内容') !!} 写文章。有发布权的人点发布，编辑默认只能存草稿或待审。</li>
    <li>打开前台看效果。换主题后还是旧样子，到{!! $hl('cache', '缓存') !!}清空。</li>
</ol>
<p class="hint">安装时勾了演示数据，前台已经有栏目和文章，改文案即可。空站则从栏目开始建。侧栏没有的项是当前账号没权限。</p>

<h3>侧栏对应什么</h3>
<table class="help-doc">
    <thead>
        <tr><th>位置</th><th>做什么</th></tr>
    </thead>
    <tbody>
        <tr><td>内容</td><td>{!! $hl('categories', '栏目') !!}、{!! $hl('entries', '文章') !!}、标签、媒体、留言、评论、回收站。更多里有推荐属性、{!! $hl('friendLinks', '友链') !!}、{!! $hl('recommends', '推荐位') !!}、{!! $hl('models', '模型') !!}、{!! $hl('menus', '导航') !!}、{!! $hl('forms', '表单') !!}。</td></tr>
        <tr><td>运营</td><td>{!! $hl('members', '会员') !!}、采集、跳转、统计、{!! $hl('cache', '缓存') !!}（页面缓存和磁盘静态页）。</td></tr>
        <tr><td>系统</td><td>{!! $hl('sites', '站点') !!}、{!! $hl('settings', '设置') !!}、{!! $hl('themes', '主题') !!}、语言、日志、管理员、权限，以及本页说明。</td></tr>
    </tbody>
</table>

<h3>栏目和文章</h3>
<p class="muted">栏目类型常见三种：<strong>列表</strong>出文档列表，<strong>单页</strong>只显示一篇（如关于我们），<strong>外链</strong>点过去是别的网址。一篇文章有一个主栏目（决定网址），也可以勾附加栏目，出现在多个列表里。</p>
<p class="muted">模型决定字段。文章模型一般是标题、封面、正文；产品会多价格等。{!! $hl('models', '模型') !!}可以复制、导出 JSON、再导入，不会删已有文档。</p>
<p class="muted">定时发布：发布时间没到，前台是 404。草稿可生成预览链接，不计入浏览、不进缓存。</p>

<h3>导航、推荐位、表单</h3>
<p class="muted">栏目勾「菜单」后，主题里 <code>@@cmsMenu</code> 没配导航时会拿这些栏目当顶栏。要精确控制顺序，用{!! $hl('menus', '导航') !!}建 header 菜单。</p>
<p class="muted">首页 Banner、最新新闻这类区块用{!! $hl('recommends', '推荐位') !!}，标识要和模板里的 <code>name</code> 对上，例如 <code>home_banner</code>。</p>
<p class="muted">首页友情链接 / 合作伙伴走侧栏「更多 → {!! $hl('friendLinks', '友链') !!}」，数据存在推荐位 <code>partners</code>（没有则用 <code>friend_links</code>）。这和导航站主题里的「链接」模型不是一回事：那个是网址目录。</p>
<p class="muted">联系表单在{!! $hl('forms', '表单') !!}里建，前台用 <code>@@cmsForm</code> 按标识输出。提交进「留言」。评论默认待审核，可在{!! $hl('settings', '设置') !!}里开关。</p>

<h3>会员</h3>
<p class="muted">前台 <code>/member</code> 可注册登录。模型勾「允许会员投稿」且分组有投稿权限后，会员能写稿，后台审过再发布。设置里可开评论、游客评论。</p>

<h3>多站、备份、缓存</h3>
<p class="muted">{!! $hl('sites', '站点') !!}按域名分站，栏目和文章带站点隔离。顶栏可切换当前编辑站。备份在{!! $hl('settingsMore', '设置 → 更多') !!}，或命令 <code>php artisan cms:backup</code>。缓存、静态页生成都在{!! $hl('cache', '缓存') !!}，nginx 写法见「<a href="{{ $urls['env'] ?? '#' }}">环境</a>」。</p>

<h3>常见问题</h3>
<div class="help-faq">
    <details>
        <summary>换了主题，前台还是旧样子</summary>
        <p>页面缓存还在。到{!! $hl('cache', '缓存') !!}点「清空缓存」，或执行 <code>php artisan cms:html-clear</code>。</p>
    </details>
    <details>
        <summary>写了文章前台看不到</summary>
        <p>确认已<strong>发布</strong>（不是草稿/待审），发布时间已到，且挂在对的栏目。编辑账号默认没有发布权，需要超管或有「发布内容」权限的人点发布。</p>
    </details>
    <details>
        <summary>图片或上传文件 404</summary>
        <p>项目根执行过 <code>php artisan storage:link</code>，且 <code>storage</code>、<code>bootstrap/cache</code> 可写。配了 CDN 时检查「设置」里的资源域名。</p>
    </details>
    <details>
        <summary>推荐位、表单是空的</summary>
        <p>后台标识要和模板参数一致：推荐位 <code>name</code>（如 <code>home_banner</code>），表单 <code>slug</code>（如 <code>contact</code>）。没启用或没条目也不会显示。</p>
    </details>
    <details>
        <summary>想重新跑安装向导</summary>
        <p>删掉 <code>storage/app/install.lock</code> 后再打开 <code>/install</code>。已有数据不会自动清空，正式站不要这样做。</p>
    </details>
    <details>
        <summary>上线打开是目录或 404</summary>
        <p>网站根必须指到项目的 <code>public</code>，不要指到项目根。完整 nginx / PHP / MySQL 见「<a href="{{ $urls['env'] ?? '#' }}">环境</a>」。</p>
    </details>
</div>
