<div class="card guide">
    <div class="pane">
        @php
            $deployUrl = $deployUrl ?? route('install', ['way' => 'deploy']);
            $webInstallUrl = $webInstallUrl ?? route('install', ['way' => 'web']);
            $copy = admin_t('guide.copy');
        @endphp
        <h2>Install with the Laravel CLI</h2>
        <p class="muted">Use this when PHP 8.2+ and Composer are already on the machine. SQLite is the default — no extra database required.</p>

        <h3>1. Prepare the code</h3>
        <div class="code">
            <button type="button" class="copy" data-copy>{{ $copy }}</button>
            <pre><code>composer install
cp .env.example .env
php artisan key:generate</code></pre>
        </div>
        <p class="hint">On Windows use <code>copy .env.example .env</code>.</p>

        <h3>2. Database (optional)</h3>
        <p class="muted">SQLite is already configured. For MySQL 5.7+ / 8.0, create an empty utf8mb4 database and edit <code>.env</code>:</p>
        <div class="code">
            <button type="button" class="copy" data-copy>{{ $copy }}</button>
            <pre><code>DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cms
DB_USERNAME=cms
DB_PASSWORD=use-a-strong-password</code></pre>
        </div>
        <p class="hint">Example: <code>CREATE DATABASE cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code> PHP needs <code>pdo_mysql</code>. Live nginx is under <a href="{{ $deployUrl }}">Environment</a>.</p>

        <h3>3. Install</h3>
        <div class="code">
            <button type="button" class="copy" data-copy>{{ $copy }}</button>
            <pre><code>php artisan cms:install --email=you@example.com --password=YourPass#1</code></pre>
        </div>
        <p class="hint">Add <code>--demo</code> for sample sections and entries. You can also pass <code>--site=My site --name=Admin</code>.</p>

        <h3>4. Run</h3>
        <div class="code">
            <button type="button" class="copy" data-copy>{{ $copy }}</button>
            <pre><code>php artisan serve</code></pre>
        </div>
        <p class="muted">Open <a href="{{ url('/') }}">{{ url('/') }}</a>. Admin: <a href="{{ url('/admin/login') }}">{{ url('/admin/login') }}</a>, with the email you set.</p>
        <p class="hint">You can skip <code>cms:install</code>: after the first two steps, finish in the browser at <a href="{{ $webInstallUrl }}">web install</a>.</p>
        <p class="hint">Do not edit core files for “when an article is published”. Listen to <code>App\Events\*</code> — see Help → Templates.</p>
    </div>
</div>
