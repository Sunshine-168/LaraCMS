@php
    $siteId = (int) ($siteId ?? 1);
    $compact = (bool) ($compact ?? false);
    $copy = admin_t('guide.copy');
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
@endphp
<div class="deploy-guide">
    @unless($compact)
        <h2>What you need to go live</h2>
        <p class="muted"><code>php artisan serve</code> is for trying locally. A real site needs PHP, a database, and nginx (or similar) pointing at <code>public</code>.</p>
    @endunless

    <h3>PHP</h3>
    <p class="muted">PHP <strong>8.2+</strong> with <code>pdo</code>, <code>mbstring</code>, <code>openssl</code>, <code>tokenizer</code>, <code>xml</code>, <code>ctype</code>, <code>json</code>, <code>fileinfo</code>, <code>curl</code>. Add <code>pdo_mysql</code> for MySQL or <code>pdo_sqlite</code> for SQLite.</p>
    <p class="muted">Watermarks and thumbs need <code>gd</code> or <code>imagick</code>. The rest still installs without them.</p>
    <p class="muted">Suggested <code>php.ini</code>:</p>
    <div class="code">
        <button type="button" class="copy" data-copy>{{ $copy }}</button>
        <pre><code>memory_limit = 256M
upload_max_filesize = 32M
post_max_size = 32M
max_execution_time = 60
date.timezone = Asia/Shanghai</code></pre>
    </div>
    <p class="hint">Restart php-fpm after changes. <code>storage</code> and <code>bootstrap/cache</code> must be writable, and you should have run <code>php artisan storage:link</code>.</p>

    <h3>MySQL</h3>
    <p class="muted">Prefer MySQL 5.7 / 8.0 or MariaDB 10.3+, charset <code>utf8mb4</code>. Create an empty database, then put the account in <code>.env</code>.</p>
    <div class="code">
        <button type="button" class="copy" data-copy>{{ $copy }}</button>
        <pre><code>CREATE DATABASE cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cms'@'127.0.0.1' IDENTIFIED BY 'use-a-strong-password';
GRANT ALL ON cms.* TO 'cms'@'127.0.0.1';
FLUSH PRIVILEGES;</code></pre>
    </div>
    <div class="code">
        <button type="button" class="copy" data-copy>{{ $copy }}</button>
        <pre><code>DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cms
DB_USERNAME=cms
DB_PASSWORD=use-a-strong-password</code></pre>
    </div>
    <p class="hint">Trying locally can stay on SQLite (<code>DB_CONNECTION=sqlite</code>). After editing <code>.env</code>, run <code>php artisan config:clear</code>.</p>

    <h3>nginx</h3>
    <p class="muted">The document root must be <code>public</code>, not the project root. Replace <code>/var/www/leafcms</code> and the PHP-FPM socket.</p>
    <div class="code">
        <button type="button" class="copy" data-copy>{{ $copy }}</button>
        <pre><code>server {
    listen 80;
    server_name www.example.com;
    root /var/www/leafcms/public;
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
    <p class="hint">The first <code>try_files</code> line prefers disk HTML from the admin. Missing files go to PHP. For extra sites, change <code>site_{{ $siteId }}</code>. Terminate HTTPS in front or in your panel.</p>
    <p class="muted">Set <code>APP_URL=https://www.example.com</code>, <code>APP_ENV=production</code>, <code>APP_DEBUG=false</code>. Behind a reverse proxy, also set <code>TRUST_PROXIES=*</code>.</p>

    <h3>Search engines and robots.txt</h3>
    <p class="muted">The front URL is <code>/robots.txt</code>. By default the home page is allowed; <code>/admin</code>, <code>/install</code>, <code>/member</code>, and <code>/preview</code> are not, and a sitemap line is included for each language. While the site is closed the whole file is <code>Disallow: /</code>.</p>
    <p class="muted">To block extra paths, add “robots.txt extra rules” under {!! $hl('settingsMore', 'Settings → More') !!}, for example:</p>
    <div class="code">
        <button type="button" class="copy" data-copy>{{ $copy }}</button>
        <pre><code>Disallow: /private
Disallow: /drafts</code></pre>
    </div>
    <p class="hint">No HTML tags. About 40 lines, 200 characters each. Check the live <code>/robots.txt</code> after saving. This is not the same as the page <code>&lt;meta name="robots"&gt;</code>. After install, pick which sections and entries to list under Operations → More → Sitemap.</p>

    <h3>Object storage for uploads</h3>
    <p class="muted">Files go to <code>storage/app/public</code> by default (<code>php artisan storage:link</code>). To only change the public host, set the CDN field under {!! $hl('settingsMore', 'Settings → More') !!}.</p>
    <p class="muted">For Aliyun OSS / Tencent COS / AWS S3: <code>composer require league/flysystem-aws-s3-v3</code>, then in <code>.env</code>:</p>
    <div class="code">
        <button type="button" class="copy" data-copy>{{ $copy }}</button>
        <pre><code>CMS_MEDIA_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=oss-cn-hangzhou
AWS_BUCKET=bucket
AWS_URL=https://bucket.oss-cn-hangzhou.aliyuncs.com
AWS_ENDPOINT=https://oss-cn-hangzhou.aliyuncs.com
AWS_USE_PATH_STYLE_ENDPOINT=false</code></pre>
    </div>
    <p class="hint">Remote disks skip local GD watermarks. Run <code>php artisan config:clear</code> after changing env.</p>

    <h3>nginx in front of Docker</h3>
    <p class="muted">If the container only publishes 8000, the browser sees the Docker gateway IP. Put nginx in front and pass the real client IP:</p>
    <div class="code">
        <button type="button" class="copy" data-copy>{{ $copy }}</button>
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
    <p class="hint">The container also needs <code>TRUST_PROXIES=*</code> (already in this repo’s <code>docker-compose.yml</code>). Port mapping alone, with no nginx, cannot see the visitor’s public IP.</p>
    @if(!empty($scheduleUrl))
        <p class="hint">Collectors and analytics prune need Laravel woken every minute — see <a href="{{ $scheduleUrl }}">Schedule</a>.</p>
    @endif
</div>
