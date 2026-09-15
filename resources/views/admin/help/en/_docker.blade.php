<div class="card guide">
    <div class="pane">
        @php $copy = admin_t('guide.copy'); @endphp
        <h2>Install with Docker</h2>
        <p class="muted">Use this with Docker Desktop. One command installs dependencies, migrates, and loads demo data.</p>

        <h3>1. Start in the project directory</h3>
        <div class="code">
            <button type="button" class="copy" data-copy>{{ $copy }}</button>
            <pre><code>docker compose up --build</code></pre>
        </div>
        <p class="hint">The first build takes a few minutes. You are ready when you see <code>Development Server</code>.</p>

        <h3>2. Open the site</h3>
        <p class="muted">Front <a href="http://127.0.0.1:8000/">http://127.0.0.1:8000/</a><br>Admin <a href="http://127.0.0.1:8000/admin/login">http://127.0.0.1:8000/admin/login</a></p>

        <h3>3. Demo accounts (local only)</h3>
        <table class="checks">
            <tr><td>Super admin</td><td><code>admin@example.com</code> / <code>Admin@12345</code></td></tr>
            <tr><td>Editor</td><td><code>editor@example.com</code> / <code>Editor@12345</code></td></tr>
            <tr><td>Member</td><td><code>member@example.com</code> / <code>Member@12345</code></td></tr>
        </table>
        <p class="hint">Change these passwords before going live. For an empty site use web install or the Laravel CLI without <code>--demo</code>.</p>

        <h3>Useful commands</h3>
        <div class="code">
            <button type="button" class="copy" data-copy>{{ $copy }}</button>
            <pre><code>docker compose down
docker compose exec app php artisan test</code></pre>
        </div>
        <p class="hint">The container working directory is <code>/var/www/html</code>; the project is mounted there.</p>
    </div>
</div>
