@extends('themes.official.partials.layout')

@section('title', cms_t('安装 — LeafCMS', 'Install — LeafCMS'))
@section('description', cms_t('LeafCMS 可以用 Docker、命令行或网页向导安装。装完即可进入网站前台和后台。', 'Install LeafCMS with Docker, the command line, or the web wizard. Then open the site and the admin.'))
@section('foot', 'LeafCMS · PHP 8.4+ · Laravel 13')

@section('content')
    <div class="page-hero">
        <div class="wrap">
            <div class="kicker"><i></i> Install</div>
            <h1>{{ cms_t('三种方式，装完即用', 'Three ways, then it is ready') }}</h1>
            <p class="lead">{{ cms_t('在项目目录里选择一种方式安装。装好后打开网站首页和后台。如果要重新安装，删掉安装标记后会再次进入向导。', 'Pick one method in the project folder. Then open the site and the admin. Delete the install lock to run the wizard again.') }}</p>
        </div>
    </div>
    <section>
        <div class="wrap stack">
            <article class="card">
                <h3>Docker</h3>
                <p>{{ cms_t('一条命令启动。第一次启动会准备好数据库，并带上演示内容。', 'One command. The first start prepares the database and demo content.') }}</p>
                <pre><code>docker compose up --build</code></pre>
            </article>
            <article class="card">
                <h3>{{ cms_t('命令行', 'Command line') }}</h3>
                <p>{{ cms_t('需要 PHP 8.4 和 Composer。默认使用 SQLite。如果改用 MySQL，先填好数据库配置再安装。', 'Needs PHP 8.4 and Composer. SQLite is the default. For MySQL, set the database config first.') }}</p>
                <pre><code>composer install
cp .env.example .env
php artisan key:generate
php artisan cms:install \
  --email=you@example.com \
  --password=YourPass#1
php artisan serve</code></pre>
                <p class="note">{{ cms_t('Windows 下复制配置文件用', 'On Windows copy the env file with') }} <code>copy .env.example .env</code>{{ cms_t('。加上', '. Add') }} <code>--demo</code> {{ cms_t('会带上示例栏目和文章。', 'to load sample sections and entries.') }}</p>
            </article>
            <article class="card">
                <h3>{{ cms_t('网页向导', 'Web wizard') }}</h3>
                <p>{{ cms_t('先启动程序，再打开安装页。页面上可以检查环境、选择数据库，并填写管理员账号。', 'Start the app, then open the install page to check the environment, pick a database, and set the admin account.') }}</p>
                <pre><code>composer install
cp .env.example .env
php artisan key:generate
php artisan serve</code></pre>
                <p class="note">{{ cms_t('即使不导入演示内容，也会创建你填写的管理员账号。', 'Skipping demo content still creates the admin account you enter.') }}</p>
            </article>
            <article class="card">
                <h3>{{ cms_t('开发环境一键种子', 'Dev seed') }}</h3>
                <pre><code>php artisan migrate:fresh --seed
php artisan storage:link</code></pre>
                <p class="note">{{ cms_t('这会重建演示数据，只适合本机试用。站点对外发布前，请更换这些初始密码。', 'This rebuilds demo data for a local trial. Change those initial passwords before the site is public.') }}</p>
            </article>
        </div>
    </section>
@endsection
