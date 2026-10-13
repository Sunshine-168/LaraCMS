@php
    $urls = $urls ?? [];
    $hl = $hl ?? fn (string $key, string $label) => e($label);
    $copy = admin_t('guide.copy');
@endphp
<h2>Schedule and queues</h2>
<p class="muted">Collectors and analytics prune do not run by themselves — the OS must wake Laravel every minute. PHP / nginx: <a href="{{ $urls['env'] ?? '#' }}">Environment</a>. Sources are in {!! $hl('collectors', 'Collectors') !!}; each one only fetches when its interval is due.</p>

<h3>What the scheduler does</h3>
<table class="help-doc">
    <thead>
        <tr><th>Job</th><th>When</th><th>Notes</th></tr>
    </thead>
    <tbody>
        <tr><td>Collect</td><td>Every minute</td><td>Each source uses its own interval</td></tr>
        <tr><td>Analytics prune</td><td>03:20 daily</td><td>Deletes hits older than 90 days by default</td></tr>
    </tbody>
</table>
<p class="hint"><code>php artisan serve</code> or Docker for trying things out can skip this. A live site needs at least the cron line below, or collection only runs from the “Collect now” button.</p>

<h3>Modes</h3>
<table class="help-doc">
    <thead>
        <tr><th>CMS_COLLECT_MODE</th><th>Behavior</th><th>Extra process</th></tr>
    </thead>
    <tbody>
        <tr><td><code>schedule</code> (default)</td><td>Fetch in the scheduler process</td><td>Only cron <code>schedule:run</code> every minute</td></tr>
        <tr><td><code>queue</code></td><td>Scheduler only enqueues jobs</td><td>cron + a long-running <code>queue:work</code></td></tr>
        <tr><td><code>off</code></td><td>No automatic runs</td><td>Admin button or a manual command</td></tr>
    </tbody>
</table>
<p class="muted"><code>CMS_STAT_PRUNE_MODE</code> uses the same three values. After editing <code>.env</code>, run <code>php artisan config:clear</code>.</p>

<h3>Recommended live minimum</h3>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
<pre><code>CMS_SCHEDULE_ENABLED=true
CMS_COLLECT_MODE=schedule
CMS_STAT_PRUNE_MODE=schedule</code></pre>
</div>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
<pre><code>* * * * * cd /var/www/leafcms && php artisan schedule:run >> /dev/null 2>&amp;1</code></pre>
</div>
<p class="hint">Replace the path with your project root. Linux: crontab. Windows: Task Scheduler, the same <code>php artisan schedule:run</code> every minute.</p>

<h3>Queue for large collects</h3>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
<pre><code>CMS_SCHEDULE_ENABLED=true
CMS_COLLECT_MODE=queue
CMS_COLLECT_QUEUE=cms
CMS_STAT_PRUNE_MODE=queue
CMS_STAT_PRUNE_QUEUE=cms
QUEUE_CONNECTION=database</code></pre>
</div>
<p class="muted">Do not use <code>QUEUE_CONNECTION=sync</code> or it still runs inline. Use <code>database</code> or Redis.</p>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
<pre><code>* * * * * cd /var/www/leafcms && php artisan schedule:run >> /dev/null 2>&amp;1
php artisan queue:work --queue=cms --sleep=1 --tries=1</code></pre>
</div>
<p class="hint">The second line must stay up (systemd / supervisor). If it dies, collect and prune stop.</p>

<h3>Your own cron, no Laravel scheduler</h3>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
<pre><code>CMS_SCHEDULE_ENABLED=false</code></pre>
</div>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
<pre><code>* * * * * cd /var/www/leafcms && php artisan cms:collect --sync
20 3 * * * cd /var/www/leafcms && php artisan cms:stat-prune --days=90 --sync</code></pre>
</div>
<p class="muted">Queue mode: replace <code>--sync</code> with <code>--queue</code> and keep <code>queue:work --queue=cms</code>.</p>

<h3>Manual commands</h3>
<div class="code">
    <button type="button" class="copy" data-copy>{{ $copy }}</button>
<pre><code>php artisan cms:collect
php artisan cms:collect --force
php artisan cms:collect --id=1 --force
php artisan cms:collect --queue
php artisan cms:collect --sync
php artisan cms:stat-prune --days=90</code></pre>
</div>
<p class="hint">Docker: <code>docker compose exec app php artisan cms:collect</code>. Demo setups usually skip cron.</p>
