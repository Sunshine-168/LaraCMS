<div class="card guide">
    <div class="pane">
        @php
            $deployUrl = $deployUrl ?? route('install', ['way' => 'deploy']);
            $webInstallUrl = $webInstallUrl ?? route('install', ['way' => 'web']);
        @endphp
        <h2>用 Laravel 命令行安装</h2>
        <p class="muted">适合本机已有 PHP 8.2+ 和 Composer 的情况。默认用 SQLite，不用另装数据库。</p>

        <h3>1. 准备代码</h3>
        <div class="code">
            <button type="button" class="copy" data-copy>复制</button>
            <pre><code>composer install
cp .env.example .env
php artisan key:generate</code></pre>
        </div>
        <p class="hint">Windows 把第二行换成 <code>copy .env.example .env</code>。</p>

            <h3>2. 数据库（可选）</h3>
        <p class="muted">默认已经是 SQLite。要用 MySQL 5.7+ / 8.0，先建空库（utf8mb4），再改 <code>.env</code>：</p>
        <div class="code">
            <button type="button" class="copy" data-copy>复制</button>
            <pre><code>DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cms
DB_USERNAME=cms
DB_PASSWORD=请换成强密码</code></pre>
        </div>
        <p class="hint">建库示例：<code>CREATE DATABASE cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code> PHP 需打开 <code>pdo_mysql</code>。上线 nginx 见 <a href="{{ $deployUrl }}">环境</a>。</p>

        <h3>3. 安装</h3>
        <div class="code">
            <button type="button" class="copy" data-copy>复制</button>
            <pre><code>php artisan cms:install --email=you@example.com --password=YourPass#1</code></pre>
        </div>
        <p class="hint">加上 <code>--demo</code> 会导入示例栏目和文章。还可以写 <code>--site=我的网站 --name=管理员</code>。</p>

        <h3>4. 启动</h3>
        <div class="code">
            <button type="button" class="copy" data-copy>复制</button>
            <pre><code>php artisan serve</code></pre>
        </div>
        <p class="muted">浏览器打开 <a href="{{ url('/') }}">{{ url('/') }}</a>，后台 <a href="{{ url('/admin/login') }}">{{ url('/admin/login') }}</a>，用上面填的邮箱登录。</p>
        <p class="hint">也可以不跑 <code>cms:install</code>，执行前两步后打开 <a href="{{ $webInstallUrl }}">网页安装</a> 用浏览器点完。</p>
        <p class="hint">二次开发不要改核心文件。文章保存、评论、表单、换主题会发出 <code>App\Events\*</code>，写法见后台说明「模板」。</p>
    </div>
</div>
