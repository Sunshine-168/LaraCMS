@php
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
@endphp
<h2>定时任务和队列</h2>
<p class="muted">采集、统计清理不会自己跑，要靠系统每分钟叫醒 Laravel。PHP / nginx 见「<a href="{{ $urls['env'] ?? '#' }}">环境</a>」。采集源在{!! $hl('collectors', '采集') !!}里配，间隔到了才会真正拉取。</p>

<h3>调度会做什么</h3>
<table class="help-doc">
    <thead>
        <tr><th>任务</th><th>何时</th><th>说明</th></tr>
    </thead>
    <tbody>
        <tr><td>采集</td><td>每分钟问一次</td><td>各采集源按自己的「间隔（分钟）」决定这次要不要抓</td></tr>
        <tr><td>统计清理</td><td>每天 03:20</td><td>默认删掉 90 天以前的访问记录</td></tr>
    </tbody>
</table>
<p class="hint">本机 <code>php artisan serve</code> 或 Docker 试用可以不配。正式站至少要有下面「纯定时」那一行 cron，否则后台点「立即采集」才能跑。</p>

<h3>怎么选模式</h3>
<table class="help-doc">
    <thead>
        <tr><th>CMS_COLLECT_MODE</th><th>行为</th><th>还要什么进程</th></tr>
    </thead>
    <tbody>
        <tr><td><code>schedule</code>（默认）</td><td>定时到了就在当前进程里抓</td><td>只要 cron 每分钟跑 <code>schedule:run</code></td></tr>
        <tr><td><code>queue</code></td><td>定时只往队列丢任务</td><td>cron + 常驻 <code>queue:work</code>（多站可共用）</td></tr>
        <tr><td><code>off</code></td><td>不自动跑</td><td>只靠后台按钮或手动命令</td></tr>
    </tbody>
</table>
<p class="muted"><code>CMS_STAT_PRUNE_MODE</code> 同样三个值。改 <code>.env</code> 后执行 <code>php artisan config:clear</code>。</p>

<h3>推荐：上线最小配置</h3>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
<pre><code>CMS_SCHEDULE_ENABLED=true
CMS_COLLECT_MODE=schedule
CMS_STAT_PRUNE_MODE=schedule</code></pre>
</div>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
<pre><code>* * * * * cd /var/www/leafcms && php artisan schedule:run >> /dev/null 2>&amp;1</code></pre>
</div>
<p class="hint">把路径换成你的项目根。Linux 用 crontab；Windows 用任务计划程序，每分钟执行同一条 <code>php artisan schedule:run</code>。</p>

<h3>采集量大时用队列</h3>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
<pre><code>CMS_SCHEDULE_ENABLED=true
CMS_COLLECT_MODE=queue
CMS_COLLECT_QUEUE=cms
CMS_STAT_PRUNE_MODE=queue
CMS_STAT_PRUNE_QUEUE=cms
QUEUE_CONNECTION=database</code></pre>
</div>
<p class="muted"><code>QUEUE_CONNECTION</code> 不要用 <code>sync</code>，否则还是当场执行，等于没队列。可用 <code>database</code> 或 Redis。</p>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
<pre><code>* * * * * cd /var/www/leafcms && php artisan schedule:run >> /dev/null 2>&amp;1
php artisan queue:work --queue=cms --sleep=1 --tries=1</code></pre>
</div>
<p class="hint">第二行要常驻（systemd / supervisor）。挂了采集和清理都不会跑。</p>

<h3>不用 Laravel 调度、自己写 cron</h3>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
<pre><code>CMS_SCHEDULE_ENABLED=false</code></pre>
</div>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
<pre><code>* * * * * cd /var/www/leafcms && php artisan cms:collect --sync
20 3 * * * cd /var/www/leafcms && php artisan cms:stat-prune --days=90 --sync</code></pre>
</div>
<p class="muted">队列版把 <code>--sync</code> 换成 <code>--queue</code>，并同样要 <code>queue:work --queue=cms</code>。</p>

<h3>手动命令</h3>
<div class="code">
    <button type="button" class="copy" data-copy>复制</button>
<pre><code>php artisan cms:collect
php artisan cms:collect --force
php artisan cms:collect --id=1 --force
php artisan cms:collect --queue
php artisan cms:collect --sync
php artisan cms:stat-prune --days=90</code></pre>
</div>
<p class="hint">Docker 试用可 <code>docker compose exec app php artisan cms:collect</code>。演示环境一般不必配 cron。</p>
