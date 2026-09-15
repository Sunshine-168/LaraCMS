<?php

namespace App\Services\Cms;

use Database\Seeders\AdminBootstrapSeeder;
use Database\Seeders\CmsSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class InstallService
{
    public function locked(): bool
    {
        return is_file($this->lockPath());
    }

    public function lockPath(): string
    {
        return storage_path('app/install.lock');
    }

    /**
     * @return list<array{key:string,label:string,ok:bool,detail:string,required:bool}>
     */
    public function checks(): array
    {
        $this->ensureEnvFile();

        $env = is_file(base_path('.env'));
        $key = $env && (string) config('app.key') !== '';

        return [
            [
                'key' => 'php',
                'label' => 'PHP 8.2 或更高',
                'ok' => version_compare(PHP_VERSION, '8.2.0', '>='),
                'detail' => PHP_VERSION,
                'required' => true,
            ],
            [
                'key' => 'pdo',
                'label' => 'PDO 扩展',
                'ok' => extension_loaded('pdo'),
                'detail' => extension_loaded('pdo') ? '已安装' : '未安装',
                'required' => true,
            ],
            [
                'key' => 'mbstring',
                'label' => 'mbstring 扩展',
                'ok' => extension_loaded('mbstring'),
                'detail' => extension_loaded('mbstring') ? '已安装' : '未安装',
                'required' => true,
            ],
            [
                'key' => 'openssl',
                'label' => 'openssl 扩展',
                'ok' => extension_loaded('openssl'),
                'detail' => extension_loaded('openssl') ? '已安装' : '未安装',
                'required' => true,
            ],
            [
                'key' => 'storage',
                'label' => 'storage 目录可写',
                'ok' => is_writable(storage_path()),
                'detail' => storage_path(),
                'required' => true,
            ],
            [
                'key' => 'cache',
                'label' => 'bootstrap/cache 可写',
                'ok' => is_dir(base_path('bootstrap/cache')) && is_writable(base_path('bootstrap/cache')),
                'detail' => $this->shortPath(base_path('bootstrap/cache')),
                'required' => true,
            ],
            [
                'key' => 'env',
                'label' => '.env 配置文件',
                'ok' => $env,
                'detail' => $env ? '已就绪' : '还没有，将从示例复制',
                'required' => true,
            ],
            [
                'key' => 'key',
                'label' => '应用密钥',
                'ok' => $key,
                'detail' => $key ? '已有' : '安装时会自动生成',
                'required' => false,
            ],
            [
                'key' => 'gd',
                'label' => 'GD 图片扩展',
                'ok' => extension_loaded('gd'),
                'detail' => extension_loaded('gd') ? '已安装' : '没有也能装，水印和缩略图不可用',
                'required' => false,
            ],
        ];
    }

    public function requiredPassed(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['required'] && ! $check['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array{ok:bool,error:?string}
     */
    public function databaseStatus(?array $input = null): array
    {
        try {
            if (is_array($input) && ($input['db_connection'] ?? '') !== '') {
                $this->applyDatabase($input);
            }
            DB::connection()->getPdo();

            return ['ok' => true, 'error' => null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $this->safeError($e)];
        }
    }

    /** @param array<string, mixed> $input */
    public function run(array $input): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(180);
        }

        $this->prepare($input);
        $this->migrate();
        $demo = ! empty($input['seed_demo']);
        if ($demo) {
            $this->seedDemo();
        }
        $this->seedAccount($input);
        $this->finish($demo);
    }

    /** @param array<string, mixed> $input */
    public function prepare(array $input): void
    {
        $this->ensureEnvFile();
        $this->ensureAppKey();
        $this->writeEnv([
            'APP_URL' => $this->appUrl($input),
            'APP_NAME' => (string) ($input['site_name'] ?? config('app.name', 'CMS')),
        ]);
        if (($input['db_connection'] ?? '') !== '') {
            $this->applyDatabase($input);
        }
        $this->ensureSqliteFile();
    }

    public function migrate(): void
    {
        Artisan::call('migrate', ['--force' => true]);
    }

    /** @param array<string, mixed> $input */
    public function seedAccount(array $input): void
    {
        AdminBootstrapSeeder::$siteName = (string) ($input['site_name'] ?? '我的网站');
        AdminBootstrapSeeder::$admin = [
            'name' => (string) ($input['admin_name'] ?? '管理员'),
            'email' => (string) ($input['admin_email'] ?? ''),
            'password' => (string) ($input['admin_password'] ?? ''),
        ];
        Artisan::call('db:seed', ['--class' => AdminBootstrapSeeder::class, '--force' => true]);
    }

    public function seedDemo(): void
    {
        Artisan::call('db:seed', ['--class' => CmsSeeder::class, '--force' => true]);
        Artisan::call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);
    }

    public function finish(bool $demo): void
    {
        Artisan::call('storage:link', ['--force' => true]);
        file_put_contents(
            $this->lockPath(),
            'installed_at='.now()->toDateTimeString()."\nseed_demo=".($demo ? '1' : '0')."\n"
        );
    }

    /** @param array<string, mixed> $input */
    public function applyDatabase(array $input): void
    {
        if (app()->environment('testing')) {
            return;
        }

        $type = (string) ($input['db_connection'] ?? '');
        if ($type === 'sqlite') {
            $path = database_path('database.sqlite');
            if (! is_file($path)) {
                touch($path);
            }
            $this->writeEnv([
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => $path,
                'DB_URL' => '',
            ]);
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $path,
            ]);
            DB::purge('sqlite');
            DB::setDefaultConnection('sqlite');
            DB::reconnect('sqlite');

            return;
        }

        if ($type !== 'mysql') {
            return;
        }

        $host = (string) ($input['db_host'] ?? '127.0.0.1');
        $port = (string) ($input['db_port'] ?? '3306');
        $database = (string) ($input['db_database'] ?? '');
        $username = (string) ($input['db_username'] ?? '');
        $password = (string) ($input['db_password'] ?? '');
        if ($database === '') {
            throw new \RuntimeException('请填写数据库名');
        }

        $this->writeEnv([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password,
            'DB_URL' => '',
        ]);
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.host' => $host,
            'database.connections.mysql.port' => $port,
            'database.connections.mysql.database' => $database,
            'database.connections.mysql.username' => $username,
            'database.connections.mysql.password' => $password,
        ]);
        DB::purge('mysql');
        DB::setDefaultConnection('mysql');
        DB::reconnect('mysql');
    }

    public function ensureEnvFile(): void
    {
        if (app()->environment('testing')) {
            return;
        }
        $path = base_path('.env');
        if (! is_file($path) && is_file(base_path('.env.example'))) {
            copy(base_path('.env.example'), $path);
        }
    }

    public function ensureAppKey(): void
    {
        if (app()->environment('testing')) {
            return;
        }
        if ((string) config('app.key') !== '') {
            return;
        }
        Artisan::call('key:generate', ['--force' => true]);
    }

    /** @param array<string, string> $pairs */
    public function writeEnv(array $pairs): void
    {
        if (app()->environment('testing')) {
            return;
        }
        $path = base_path('.env');
        if (! is_file($path)) {
            $this->ensureEnvFile();
        }
        if (! is_file($path) || ! is_writable($path)) {
            throw new \RuntimeException('.env 无法写入，请检查文件权限');
        }
        $content = (string) file_get_contents($path);
        foreach ($pairs as $key => $value) {
            $line = $key.'='.$this->envEscape((string) $value);
            if (preg_match('/^'.preg_quote($key, '/').'=.*/m', $content)) {
                $content = preg_replace('/^'.preg_quote($key, '/').'=.*/m', $line, $content, 1) ?? $content;
            } else {
                $content = rtrim($content)."\n".$line."\n";
            }
        }
        file_put_contents($path, $content);
    }

    /** @param array<string, mixed> $input */
    protected function appUrl(array $input): string
    {
        $url = trim((string) ($input['app_url'] ?? ''));
        if ($url !== '') {
            return rtrim($url, '/');
        }

        return rtrim((string) config('app.url', 'http://127.0.0.1:8000'), '/');
    }

    protected function ensureSqliteFile(): void
    {
        if (config('database.default') !== 'sqlite') {
            return;
        }
        $path = (string) config('database.connections.sqlite.database');
        if ($path === '' || $path === ':memory:') {
            return;
        }
        if (! is_file($path)) {
            $dir = dirname($path);
            if (! is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            touch($path);
        }
    }

    protected function envEscape(string $value): string
    {
        if ($value === '') {
            return '';
        }
        if (preg_match('/[\s#"\'\\\\]/', $value)) {
            return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
        }

        return $value;
    }

    protected function safeError(\Throwable $e): string
    {
        $msg = trim($e->getMessage());
        $msg = preg_replace('/SQLSTATE\[[^\]]+\]\s*/', '', $msg) ?: $msg;
        $msg = preg_replace('/\(Connection: [^)]+\)\s*/', '', $msg) ?: $msg;

        return mb_substr($msg, 0, 180);
    }

    protected function shortPath(string $path): string
    {
        $base = base_path();
        if (str_starts_with($path, $base)) {
            return ltrim(substr($path, strlen($base)), '/\\');
        }

        return $path;
    }
}
