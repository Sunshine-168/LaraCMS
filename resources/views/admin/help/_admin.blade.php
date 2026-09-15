@php
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
@endphp
<p class="muted">按侧栏把后台每一项说一遍。各功能页顶部也有一句说明。先上手看「<a href="{{ route('admin.help') }}">怎么用</a>」，换皮看「<a href="{{ $urls['helpThemes'] ?? '#' }}">主题</a>」。</p>

<h3>概览</h3>
<table class="help-doc">
    <thead>
        <tr><th>页面</th><th>做什么</th></tr>
    </thead>
    <tbody>
        <tr><td>{!! $hl('dashboard', '仪表盘') !!}</td><td>待审内容、未读留言、待审评论。快捷入口可在仪表盘里改。</td></tr>
        <tr><td>{!! $hl('stats', '统计') !!}</td><td>前台访问的 PV / UV / IP、热门页、来路。另有蜘蛛抓取。后台路径不记。</td></tr>
    </tbody>
</table>

<h3>内容</h3>
<table class="help-doc">
    <thead>
        <tr><th>页面</th><th>做什么</th></tr>
    </thead>
    <tbody>
        <tr><td>{!! $hl('entries', '内容') !!}</td><td>写文章。有「发布内容」权限才能发布；编辑默认存草稿或待审。可定时发布、生成预览链接、还原上一稿。一篇有一个主栏目（决定网址），可勾附加栏目。</td></tr>
        <tr><td>{!! $hl('categories', '栏目') !!}</td><td>频道结构。列表出文档、单页只显示一篇、外链跳到别的网址。栏目要绑模型。可设列表/详情模板。</td></tr>
        <tr><td>{!! $hl('entryTags', '标签') !!}</td><td>给文章打标签，前台 <code>/tag/标识</code> 聚合。和「说明 → 标签」（模板指令）不是一回事。</td></tr>
        <tr><td>{!! $hl('media', '媒体') !!}</td><td>上传图片和文件。正文插图、推荐位封面也从这里选。打不开时先执行 <code>php artisan storage:link</code>。只换访问域名在{!! $hl('settingsMore', '设置 → 更多') !!}填 CDN；文件改存 OSS / S3 用 <code>CMS_MEDIA_DISK</code>，见「<a href="{{ $urls['env'] ?? '#' }}">环境</a>」。水印只对本地盘有效。</td></tr>
        <tr><td>{!! $hl('submissions', '留言') !!}</td><td>前台表单提交的收件箱。可已读、备注、邮件回复。</td></tr>
        <tr><td>{!! $hl('comments', '评论') !!}</td><td>默认待审核，通过后出现在文章下。开关和运营通知邮箱在{!! $hl('settings', '设置') !!}。</td></tr>
        <tr><td>{!! $hl('recycle', '回收站') !!}</td><td>删除的内容先在这里。可还原；彻底删除不能恢复。侧栏在内容「更多」里，内容页右上角也能进。</td></tr>
        <tr><td>{!! $hl('flags', '推荐') !!}</td><td>写内容时勾的属性（如头条）。模板 <code>@@cmsContent(['flag' =&gt; '属性标识'])</code> 按它筛选。</td></tr>
        <tr><td>{!! $hl('recommends', '推荐位') !!}</td><td>首页 Banner 这类区块。标识码要和模板 <code>@@cmsAdv(['name' =&gt; 'home_banner'])</code> 一致。可手填条目或按规则取文章。手填条目点「选择」从媒体库挑图，保存前能预览。友链不必进这里改，用「运营 → 更多 → 友链」。</td></tr>
        <tr><td>{!! $hl('models', '模型') !!}</td><td>一类内容的字段（价格、附件等）。可复制、导出/导入 JSON，不删已有文章。勾「允许会员投稿」后前台会员才能写这类稿。</td></tr>
        <tr><td>{!! $hl('menus', '导航') !!}</td><td>顶栏菜单。位置如 <code>header</code> 要和 <code>@@cmsMenu</code> 一致。没建菜单时会用勾了「菜单」的栏目凑。</td></tr>
        <tr><td>{!! $hl('forms', '表单') !!}</td><td>定义联系表单字段。标识码给 <code>@@cmsForm(['slug' =&gt; 'contact'])</code>。提交进留言。</td></tr>
    </tbody>
</table>

<h3>运营</h3>
<table class="help-doc">
    <thead>
        <tr><th>页面</th><th>做什么</th></tr>
    </thead>
    <tbody>
        <tr><td>{!! $hl('members', '会员') !!}</td><td>前台注册用户名单。可停用。投稿、收藏在会员中心。</td></tr>
        <tr><td>{!! $hl('collectors', '采集') !!}</td><td>从 RSS、JSON 或 HTML 列表把公开内容拉进栏目。先试抓再启用。只采集你有权使用的源，不爬内网。自动跑需要配 cron，见「<a href="{{ $urls['schedule'] ?? '#' }}">定时</a>」。</td></tr>
        <tr><td>{!! $hl('cache', '缓存') !!}</td><td>全页缓存（访问后把整页存起来）和磁盘静态页（写成 <code>public/html/</code> 文件）。生成、删除文件多时都会分批进行，页面上有进度。右上角「清空缓存」会清模板/配置和已存页面。nginx 写法见「<a href="{{ $urls['env'] ?? '#' }}">环境</a>」。</td></tr>
        <tr><td>{!! $hl('friendLinks', '友链') !!}</td><td>首页友情链接 / 合作伙伴。侧栏在运营「更多」。不是导航站的网址目录。数据写在推荐位 <code>partners</code>（没有则用 <code>friend_links</code>），主题用 <code>@@cmsAdv(['name' =&gt; 'partners'])</code> 输出。</td></tr>
        <tr><td>{!! $hl('sitemap', '地图') !!}</td><td>给搜索引擎的站点地图。侧栏在运营「更多」。XML 在 <code>/sitemap.xml</code>，网页版在 <code>/sitemap</code>。可勾栏目 / 文章 / 标签，并可加补充地址。会员门槛文章、没有公开文章的标签不列入。<code>robots.txt</code> 会列出各语言的 XML。</td></tr>
        <tr><td>{!! $hl('redirects', '跳转') !!}</td><td>旧地址 301/302 到新地址。按站点隔离，命中会计数。停用后规则还在，只是不跳。</td></tr>
        <tr><td>{!! $hl('groups', '分组') !!}</td><td>会员能力（如投稿）。停用后这组前台权限关掉，人还在名单里。</td></tr>
    </tbody>
</table>

<h3>系统</h3>
<table class="help-doc">
    <thead>
        <tr><th>页面</th><th>做什么</th></tr>
    </thead>
    <tbody>
        <tr><td>{!! $hl('sites', '站点') !!}</td><td>按域名分站，栏目和文章隔离。顶栏切换正在改哪一个。新建站会复制基础设置，不复制内容。默认站不能删，删站会连内容一起删。</td></tr>
        <tr><td>{!! $hl('settings', '设置') !!}</td><td>网站名称、关键词。外观：Logo、主色、页脚。评论规则、SMTP 发信。登录页可找回后台密码。更多：文章链接格式、robots.txt 补充规则、手机外观、备份还原、API Token、CDN 域名。对象存储盘不在表单里改，见「<a href="{{ $urls['env'] ?? '#' }}">环境</a>」。</td></tr>
        <tr><td>{!! $hl('themes', '主题') !!}</td><td>点某一套进入配置：这一套的多语言短句、要补的模型、自定义参数（<code>cms_theme('键')</code>）。「用于本站」会补缺的模型，不改已有文章。详见「<a href="{{ $urls['helpThemes'] ?? '#' }}">主题</a>」。</td></tr>
        <tr><td>{!! $hl('locales', '语言') !!}</td><td>三件事分开：<strong>内容语言</strong>（文章标签、/en 前缀）、<strong>后台界面</strong>（侧栏中/英）、<strong>主题文案</strong>（搜索、登录、评论、会员等短句，用 <code>cms_ui('search')</code>）。导航和推荐位会按已添加的语言出输入框。译文统计只算该语言真正填了标题的篇数；停用不删已有译文。改语言或主题文案会清全页缓存和磁盘静态页。</td></tr>
        <tr><td>{!! $hl('logs', '日志') !!}</td><td>管理员在后台做过什么：改了哪一篇、批量动作、登录和 IP。IP 旁会标本机、Docker 内网或公网省市。点 IP 可只看这个地址。生成静态页的进度请求不记。不能当文章修订用。</td></tr>
        <tr><td>{!! $hl('profile', '账号') !!}</td><td>右上角点自己的名字。改显示名称和登录密码。</td></tr>
        <tr><td>{!! $hl('adminUsers', '管理员') !!}</td><td>后台账号、角色、可管理哪些站点。可限制只能改某些栏目。</td></tr>
        <tr><td>{!! $hl('roles', '权限') !!}</td><td>角色勾模块权限。超级管理员能做所有事，不能删。「发布内容」单独一项，没有就不能点发布。</td></tr>
        <tr><td>说明</td><td>本页。人人都能打开，不跟运营权限走，所以放在系统最底下，而不是运营「更多」。</td></tr>
    </tbody>
</table>
<p class="hint">侧栏没有的项是当前账号没权限，不是系统缺功能。模板指令见「<a href="{{ $urls['tags'] ?? '#' }}">标签</a>」。</p>
