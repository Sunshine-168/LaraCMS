@php
    $siteId = (int) ($siteId ?? 1);
    $compact = (bool) ($compact ?? false);
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
@endphp
<div class="deploy-guide">
    @unless($compact)
        <h2>上线要准备什么</h2>
        <p class="muted">本机 <code>php artisan serve</code> 只适合试用。正式站点需要 PHP、数据库，以及 nginx（或同类）把请求指到 <code>public</code> 目录。</p>
    @endunless

    <h3>PHP 环境</h3>
    <p class="muted">需要 PHP <strong>8.2 或更高</strong>，并打开这些扩展：<code>pdo</code>、<code>mbstring</code>、<code>openssl</code>、<code>tokenizer</code>、<code>xml</code>、<code>ctype</code>、<code>json</code>、<code>fileinfo</code>、<code>curl</code>。用 MySQL 再开 <code>pdo_mysql</code>；用 SQLite 再开 <code>pdo_sqlite</code>。</p>
    <p class="muted">图片水印和缩略图需要 <code>gd</code> 或 <code>imagick</code>，没有也能装，只是这两项不可用。</p>
    <p class="muted">建议在 <code>php.ini</code> 里：</p>
    <div class="code">
        <button type="button" class="copy" data-copy>复制</button>
        <pre><code>memory_limit = 256M
upload_max_filesize = 32M
post_max_size = 32M
max_execution_time = 60
date.timezone = Asia/Shanghai</code></pre>
    </div>
    <p class="hint">改完后重启 php-fpm 或网站服务。目录 <code>storage</code> 和 <code>bootstrap/cache</code> 必须可写。宝塔若禁用了 <code>exec</code> / <code>symlink</code>，不必再跑 <code>php artisan storage:link</code>，上传文件由站点自己从 <code>/storage</code> 读出。要做成真正软链（图片不走 PHP），在宝塔终端执行 <code>ln -s 站点目录/storage/app/public 站点目录/public/storage</code>。</p>

    <h3>MySQL</h3>
    <p class="muted">正式站点建议 MySQL 5.7 / 8.0 或 MariaDB 10.3+，字符集用 <code>utf8mb4</code>。先建空库，再把账号写进 <code>.env</code>。</p>
    <div class="code">
        <button type="button" class="copy" data-copy>复制</button>
        <pre><code>CREATE DATABASE cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cms'@'127.0.0.1' IDENTIFIED BY '请换成强密码';
GRANT ALL ON cms.* TO 'cms'@'127.0.0.1';
FLUSH PRIVILEGES;</code></pre>
    </div>
    <div class="code">
        <button type="button" class="copy" data-copy>复制</button>
        <pre><code>DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cms
DB_USERNAME=cms
DB_PASSWORD=请换成强密码</code></pre>
    </div>
    <p class="hint">试用可以继续用 SQLite（<code>DB_CONNECTION=sqlite</code>），不用另装数据库。改完 <code>.env</code> 后执行 <code>php artisan config:clear</code>。</p>

    <h3>nginx</h3>
    <p class="muted">网站根目录必须是项目里的 <code>public</code>，不要指到项目根。下面把 <code>/var/www/laracms</code> 换成你的路径，<code>php8.3-fpm.sock</code> 换成本机 PHP-FPM 套接字。</p>
    <div class="code">
        <button type="button" class="copy" data-copy>复制</button>
        <pre><code>server {
    listen 80;
    server_name www.example.com;
    root /var/www/laracms/public;
    index index.php;
    charset utf-8;
    client_max_body_size 32m;

    location / {
        try_files /html/site_{{ $siteId }}$uri/index.html /html/site_{{ $siteId }}$uri $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}</code></pre>
    </div>
    <p class="hint">第一行 <code>try_files</code> 会优先读后台生成的磁盘静态页。没有文件时再交给 PHP。多站点把 <code>site_{{ $siteId }}</code> 改成对应站的编号。HTTPS 请在前面加证书或由面板处理。</p>
    <p class="muted">还要在 <code>.env</code> 写上 <code>APP_URL=https://www.example.com</code>，<code>APP_ENV=production</code>，<code>APP_DEBUG=false</code>。若站点在反向代理后面，再设 <code>TRUST_PROXIES=*</code>。</p>

    <h3>搜索引擎 robots.txt</h3>
    <p class="muted">前台地址是 <code>/robots.txt</code>。默认允许抓首页，禁止 <code>/admin</code>、<code>/install</code>、<code>/member</code>、<code>/preview</code>，并带上各语言的 sitemap。关站时整站是 <code>Disallow: /</code>。</p>
    <p class="muted">还要挡住某几段路径时，到{!! $hl('settingsMore', '设置 → 更多') !!}写「robots.txt 补充规则」，例如：</p>
    <div class="code">
        <button type="button" class="copy" data-copy>复制</button>
        <pre><code>Disallow: /private
Disallow: /drafts</code></pre>
    </div>
    <p class="hint">不要写 HTML 标签。大约最多 40 行、每行 200 字。改完打开前台 <code>/robots.txt</code> 核对。这和页面里的 <code>&lt;meta name="robots"&gt;</code> 不是一回事。收录范围装好后在后台「运营 → 更多 → 地图」（站点地图）。</p>

    <h3>图片放到对象存储</h3>
    <p class="muted">默认传到本机 <code>storage/app/public</code>。有软链时 nginx 直接吐文件；没有软链时走站点 <code>/storage</code> 路由，不依赖宝塔放开 <code>exec</code>。只换访问域名时，在{!! $hl('settingsMore', '设置 → 更多') !!}填「图片走这个域名」，不必改盘。</p>
    <p class="muted">要用阿里云 OSS / 腾讯云 COS / AWS S3 存文件：先 <code>composer require league/flysystem-aws-s3-v3</code>，再在 <code>.env</code> 写：</p>
    <div class="code">
        <button type="button" class="copy" data-copy>复制</button>
        <pre><code>CMS_MEDIA_DISK=s3
AWS_ACCESS_KEY_ID=你的 Key
AWS_SECRET_ACCESS_KEY=你的 Secret
AWS_DEFAULT_REGION=oss-cn-hangzhou
AWS_BUCKET=桶名
AWS_URL=https://桶名.oss-cn-hangzhou.aliyuncs.com
AWS_ENDPOINT=https://oss-cn-hangzhou.aliyuncs.com
AWS_USE_PATH_STYLE_ENDPOINT=false</code></pre>
    </div>
    <p class="hint">对象存储上不跑本机 GD 水印。需要水印时先在本机盘处理，或用 CDN 的图片处理。改完执行 <code>php artisan config:clear</code>。</p>

    <h3>nginx 反代到 Docker</h3>
    <p class="muted">容器只映射 8000 时，浏览器直连看到的是 Docker 网关 IP。前面加 nginx，把真实客户端 IP 传进去：</p>
    <div class="code">
        <button type="button" class="copy" data-copy>复制</button>
        <pre><code>location / {
    proxy_pass http://127.0.0.1:8000;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
    proxy_set_header X-Forwarded-Host $host;
    proxy_set_header X-Forwarded-Port $server_port;
}</code></pre>
    </div>
    <p class="hint">容器环境变量里也要有 <code>TRUST_PROXIES=*</code>（本仓库 <code>docker-compose.yml</code> 已写上）。只映射端口、前面没有 nginx 时，程序拿不到访客公网 IP。</p>
    @if(!empty($scheduleUrl))
        <p class="hint">采集和统计清理要每分钟叫醒 Laravel，见「<a href="{{ $scheduleUrl }}">定时</a>」。</p>
    @endif
</div>
