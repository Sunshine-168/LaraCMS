<div class="card guide">
    <div class="pane">
        <h2>用 Docker 安装</h2>
        <p class="muted">适合本机已装 Docker Desktop。一条命令会装依赖、建表，并导入演示数据。</p>

        <h3>1. 进入项目目录后启动</h3>
        <div class="code">
            <button type="button" class="copy" data-copy>复制</button>
            <pre><code>docker compose up --build</code></pre>
        </div>
        <p class="hint">第一次会编译镜像，需要几分钟。看到 <code>Development Server</code> 就好了。</p>

        <h3>2. 打开网站</h3>
        <p class="muted">前台 <a href="http://127.0.0.1:8000/">http://127.0.0.1:8000/</a><br>后台 <a href="http://127.0.0.1:8000/admin/login">http://127.0.0.1:8000/admin/login</a></p>

        <h3>3. 演示账号（仅本地）</h3>
        <table class="checks">
            <tr><td>超级管理员</td><td><code>admin@example.com</code> / <code>Admin@12345</code></td></tr>
            <tr><td>编辑</td><td><code>editor@example.com</code> / <code>Editor@12345</code></td></tr>
            <tr><td>会员</td><td><code>member@example.com</code> / <code>Member@12345</code></td></tr>
        </table>
        <p class="hint">上线前必须改掉这些密码。不要演示数据时，用网页安装或 Laravel 命令行，不要加 <code>--demo</code>。</p>

        <h3>常用命令</h3>
        <div class="code">
            <button type="button" class="copy" data-copy>复制</button>
            <pre><code>docker compose down
docker compose exec app php artisan test</code></pre>
        </div>
        <p class="hint">容器工作目录是 <code>/var/www/html</code>，项目文件会挂进去。</p>
    </div>
</div>
