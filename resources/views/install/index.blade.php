<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>安装 CMS</title>
    <link rel="stylesheet" href="{{ asset('css/install.css') }}?v=2">
</head>
<body>
<div class="wrap">
    <div class="brand"><span class="logo">C</span> CMS 安装向导</div>
    <nav class="ways" aria-label="安装方式">
        <a class="{{ ($way ?? 'web') === 'web' ? 'is-on' : '' }}" href="{{ route('install', ['way' => 'web']) }}">网页安装</a>
        <a class="{{ ($way ?? 'web') === 'laravel' ? 'is-on' : '' }}" href="{{ route('install', ['way' => 'laravel']) }}">Laravel 命令行</a>
        <a class="{{ ($way ?? 'web') === 'docker' ? 'is-on' : '' }}" href="{{ route('install', ['way' => 'docker']) }}">Docker</a>
        <a class="{{ ($way ?? 'web') === 'deploy' ? 'is-on' : '' }}" href="{{ route('install', ['way' => 'deploy']) }}">上线部署</a>
    </nav>
    @if(($way ?? 'web') === 'web')
    <div class="card">
        <aside class="rail">
            <h1>三步即可用</h1>
            <p class="lead">检查环境、选好数据库、创建管理员。装好就能进后台。</p>
            <ol class="steps" id="installSteps">
                <li class="is-on" data-step="0"><span class="n">1</span><span>环境检查</span></li>
                <li data-step="1"><span class="n">2</span><span>数据库</span></li>
                <li data-step="2"><span class="n">3</span><span>网站和管理员</span></li>
            </ol>
        </aside>
        <div class="pane">
            @if(session('error'))<div class="flash">{{ session('error') }}</div>@endif
            @if($errors->any())<div class="flash">{{ $errors->first() }}</div>@endif

            <form method="post" action="{{ route('install.run') }}" id="installForm">
                @csrf
                <input type="hidden" name="app_url" value="{{ rtrim(request()->getSchemeAndHttpHost(), '/') }}">

                <section data-panel="0">
                    <h2>先看这台电脑能不能装</h2>
                    <p class="muted">红字必须先修好。黄字不影响安装，装好后再补也行。</p>
                    <table class="checks">
                        @foreach($checks as $check)
                            <tr>
                                <td>{{ $check['label'] }}</td>
                                <td class="{{ $check['ok'] ? 'ok' : ($check['required'] ? 'bad' : 'warn') }}">
                                    {{ $check['ok'] ? '通过' : ($check['required'] ? '未通过' : '建议补上') }}
                                    · {{ $check['detail'] }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                    <p class="hint">还需要 <code>tokenizer</code>、<code>xml</code>、<code>ctype</code>、<code>json</code>、<code>fileinfo</code>、<code>curl</code>。用 MySQL 再开 <code>pdo_mysql</code>。完整 php.ini 和 nginx 见 <a href="{{ route('install', ['way' => 'deploy']) }}">上线部署</a>。</p>
                    <div class="actions">
                        <span></span>
                        <button class="btn" type="button" data-next @disabled(! $requiredOk)>下一步：数据库</button>
                    </div>
                    @unless($requiredOk)
                        <p class="bad">请先处理标成未通过的项，再继续。</p>
                    @endunless
                </section>

                <section data-panel="1" hidden>
                    <h2>数据存在哪里</h2>
                    <p class="muted">试用选文件数据库即可，不用另装软件。正式站点更常用 MySQL。</p>
                    <div class="choice" id="dbChoice">
                        <label class="is-on">
                            <input type="radio" name="db_connection" value="sqlite" checked>
                            <strong>文件数据库</strong>
                            <span>SQLite，适合先试用。数据写在网站目录里。</span>
                        </label>
                        <label>
                            <input type="radio" name="db_connection" value="mysql">
                            <strong>MySQL</strong>
                            <span>请先建好空库，再填下面的连接信息。</span>
                        </label>
                    </div>
                    <div id="mysqlFields" hidden>
                        <p class="hint">先在 MySQL 里建空库（字符集 utf8mb4），再填连接信息。例如：<code>CREATE DATABASE cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code></p>
                        <label for="db_host">主机</label>
                        <input id="db_host" type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}">
                        <label for="db_port">端口</label>
                        <input id="db_port" type="text" name="db_port" value="{{ old('db_port', '3306') }}">
                        <label for="db_database">数据库名</label>
                        <input id="db_database" type="text" name="db_database" value="{{ old('db_database') }}" placeholder="cms">
                        <label for="db_username">用户名</label>
                        <input id="db_username" type="text" name="db_username" value="{{ old('db_username', 'root') }}">
                        <label for="db_password">密码</label>
                        <input id="db_password" type="password" name="db_password" value="{{ old('db_password') }}" autocomplete="new-password">
                    </div>
                    <p class="hint" id="dbHint">
                        @if($dbOk)
                            当前配置已经能连上数据库。
                        @else
                            当前还连不上：{{ $dbError }}。选好方式后点下一步会再测一次。
                        @endif
                    </p>
                    <div class="actions">
                        <button class="btn-muted" type="button" data-prev>上一步</button>
                        <button class="btn" type="button" data-next-db>下一步：管理员</button>
                    </div>
                </section>

                <section data-panel="2" hidden>
                    <h2>网站和管理员</h2>
                    <p class="muted">这个账号用来登录后台。请用你自己的邮箱，不要沿用示例账号。</p>
                    <label for="site_name">网站名称</label>
                    <input id="site_name" type="text" name="site_name" value="{{ old('site_name', '我的网站') }}" required maxlength="80">
                    <label for="admin_name">管理员姓名</label>
                    <input id="admin_name" type="text" name="admin_name" value="{{ old('admin_name', '管理员') }}" required maxlength="50">
                    <label for="admin_email">管理员邮箱</label>
                    <input id="admin_email" type="email" name="admin_email" value="{{ old('admin_email') }}" required maxlength="120" placeholder="you@example.com" autocomplete="username">
                    <label for="admin_password">密码（至少 8 位）</label>
                    <input id="admin_password" type="password" name="admin_password" required minlength="8" autocomplete="new-password">
                    <label for="admin_password_confirmation">再输一次密码</label>
                    <input id="admin_password_confirmation" type="password" name="admin_password_confirmation" required minlength="8" autocomplete="new-password">
                    <label class="checkline">
                        <input type="checkbox" name="seed_demo" value="1" @checked(old('seed_demo', true))>
                        <span>带上示例栏目和文章，方便先点一遍后台。正式上线前可以再清空。</span>
                    </label>
                    <div class="progress" id="installProgress" hidden>
                        <div class="bar"><i id="installBar"></i></div>
                        <ul>
                            <li data-phase="prepare">准备配置</li>
                            <li data-phase="migrate">建立数据表</li>
                            <li data-phase="demo">导入示例内容</li>
                            <li data-phase="account">创建管理员</li>
                            <li data-phase="finish">完成</li>
                        </ul>
                    </div>
                    <div class="actions">
                        <button class="btn-muted" type="button" data-prev>上一步</button>
                        <button class="btn" type="submit" id="installSubmit">开始安装</button>
                    </div>
                </section>
            </form>
        </div>
    </div>
    @elseif(($way ?? 'web') === 'laravel')
        @include('install._laravel')
    @elseif(($way ?? 'web') === 'docker')
        @include('install._docker')
    @else
        <div class="card guide">
            <div class="pane">
                @include('install._deploy')
            </div>
        </div>
    @endif
</div>
@if(($way ?? 'web') === 'web')
<script>
(function () {
    var form = document.getElementById('installForm');
    if (!form) return;
    var panels = Array.prototype.slice.call(form.querySelectorAll('[data-panel]'));
    var steps = Array.prototype.slice.call(document.querySelectorAll('#installSteps li'));
    var step = 0;
    var csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var mysql = document.getElementById('mysqlFields');
    var dbHint = document.getElementById('dbHint');
    var choice = document.getElementById('dbChoice');
    var progress = document.getElementById('installProgress');
    var bar = document.getElementById('installBar');
    var submit = document.getElementById('installSubmit');

    function show(i) {
        step = i;
        panels.forEach(function (p, idx) { p.hidden = idx !== i; });
        steps.forEach(function (s, idx) {
            s.classList.toggle('is-on', idx === i);
            s.classList.toggle('is-done', idx < i);
        });
    }

    function dbType() {
        var on = form.querySelector('[name="db_connection"]:checked');
        return on ? on.value : 'sqlite';
    }

    function syncDb() {
        var mysqlOn = dbType() === 'mysql';
        if (mysql) mysql.hidden = !mysqlOn;
        if (choice) {
            choice.querySelectorAll('label').forEach(function (lab) {
                lab.classList.toggle('is-on', lab.querySelector('input')?.checked);
            });
        }
    }

    form.querySelectorAll('[name="db_connection"]').forEach(function (el) {
        el.addEventListener('change', syncDb);
    });
    syncDb();

    form.querySelectorAll('[data-next]').forEach(function (btn) {
        btn.addEventListener('click', function () { show(step + 1); });
    });
    form.querySelectorAll('[data-prev]').forEach(function (btn) {
        btn.addEventListener('click', function () { show(Math.max(0, step - 1)); });
    });

    form.querySelector('[data-next-db]')?.addEventListener('click', async function () {
        var btn = this;
        btn.disabled = true;
        if (dbHint) dbHint.textContent = '正在测试连接…';
        try {
            var body = {
                db_connection: dbType(),
                db_host: form.db_host.value,
                db_port: form.db_port.value,
                db_database: form.db_database.value,
                db_username: form.db_username.value,
                db_password: form.db_password.value
            };
            var res = await fetch(@json(route('install.probe')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(body)
            });
            var data = await res.json().catch(function () { return {}; });
            if (!res.ok || !data.ok) {
                throw new Error(data.message || '连不上数据库');
            }
            if (dbHint) dbHint.textContent = data.message || '数据库可以连接';
            show(2);
        } catch (e) {
            if (dbHint) dbHint.textContent = e.message || '连不上数据库';
        }
        btn.disabled = false;
    });

    var phases = ['prepare', 'migrate', 'demo', 'account', 'finish'];
    form.addEventListener('submit', async function (ev) {
        if (!window.fetch) return;
        ev.preventDefault();
        if (submit) submit.disabled = true;
        if (progress) progress.hidden = false;
        var fd = new FormData(form);
        try {
            for (var i = 0; i < phases.length; i++) {
                mark(phases[i], 'on');
                if (bar) bar.style.width = Math.round((i / phases.length) * 100) + '%';
                fd.set('phase', phases[i]);
                var res = await fetch(@json(route('install.task')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: fd
                });
                var data = await res.json().catch(function () { return {}; });
                if (!res.ok || data.ok === false) {
                    throw new Error(data.message || '这一步没有完成');
                }
                mark(phases[i], 'ok');
                if (bar) bar.style.width = Math.round(((i + 1) / phases.length) * 100) + '%';
            }
            window.location.href = @json(route('install.done'));
        } catch (e) {
            mark(currentOn(), 'bad');
            alert(e.message || '安装失败');
            if (submit) submit.disabled = false;
        }
    });

    function mark(phase, state) {
        var li = progress && progress.querySelector('[data-phase="' + phase + '"]');
        if (!li) return;
        li.classList.remove('is-on', 'is-ok', 'is-bad');
        li.classList.add(state === 'ok' ? 'is-ok' : (state === 'bad' ? 'is-bad' : 'is-on'));
    }
    function currentOn() {
        var li = progress && progress.querySelector('.is-on');
        return li ? li.getAttribute('data-phase') : '';
    }
})();
</script>
@endif
<script>
document.querySelectorAll('[data-copy]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var code = btn.parentElement && btn.parentElement.querySelector('code');
        if (!code || !navigator.clipboard) return;
        navigator.clipboard.writeText(code.innerText).then(function () {
            btn.textContent = '已复制';
            setTimeout(function () { btn.textContent = '复制'; }, 1500);
        });
    });
});
</script>
</body>
</html>
