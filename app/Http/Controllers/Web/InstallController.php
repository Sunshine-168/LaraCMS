<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Cms\InstallService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstallController extends Controller
{
    public function __construct(private readonly InstallService $install) {}

    public function index(Request $request): View|RedirectResponse
    {
        $way = (string) $request->query('way', 'web');
        if (! in_array($way, ['web', 'laravel', 'docker', 'deploy'], true)) {
            $way = 'web';
        }
        if ($this->install->locked() && $way === 'web') {
            return redirect('/admin/login');
        }

        return view('install.index', $this->pageData($request));
    }

    public function probe(Request $request): JsonResponse
    {
        if ($this->install->locked()) {
            return response()->json(['ok' => false, 'message' => '已经安装过了'], 422);
        }

        $request->validate([
            'db_connection' => 'required|in:sqlite,mysql',
            'db_host' => 'nullable|string|max:120',
            'db_port' => 'nullable|string|max:8',
            'db_database' => 'nullable|string|max:120',
            'db_username' => 'nullable|string|max:120',
            'db_password' => 'nullable|string|max:200',
        ]);

        if ($request->input('db_connection') === 'mysql' && trim((string) $request->input('db_database')) === '') {
            return response()->json(['ok' => false, 'message' => '请填写数据库名'], 422);
        }

        try {
            $status = $this->install->databaseStatus($request->all());
            if (! $status['ok']) {
                return response()->json(['ok' => false, 'message' => $status['error'] ?: '连不上数据库'], 422);
            }

            return response()->json(['ok' => true, 'message' => '数据库可以连接']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function task(Request $request): JsonResponse
    {
        if ($this->install->locked() && ! $request->session()->get('install.running')) {
            return response()->json(['ok' => false, 'message' => '已经安装过了'], 422);
        }

        $phase = (string) $request->input('phase');
        if (in_array($phase, ['prepare', 'account'], true)) {
            $this->validateAccount($request);
        }

        try {
            if ($phase === 'prepare') {
                $request->session()->put('install.running', true);
                $this->install->prepare($this->payload($request));
            } elseif ($phase === 'migrate') {
                $this->install->migrate();
            } elseif ($phase === 'demo') {
                if ($request->boolean('seed_demo')) {
                    $this->install->seedDemo();
                }
            } elseif ($phase === 'account') {
                $this->install->seedAccount($this->payload($request));
            } elseif ($phase === 'finish') {
                $this->install->finish($request->boolean('seed_demo'));
                $request->session()->forget('install.running');
                $this->flashDone($request);
            } else {
                return response()->json(['ok' => false, 'message' => '未知步骤'], 422);
            }

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            $request->session()->forget('install.running');
            @unlink($this->install->lockPath());

            return response()->json(['ok' => false, 'message' => '安装失败：'.$e->getMessage()], 500);
        }
    }

    public function run(Request $request): RedirectResponse
    {
        if ($this->install->locked()) {
            return redirect('/admin/login');
        }

        $this->validateAccount($request);

        try {
            $this->install->run($this->payload($request));
        } catch (\Throwable $e) {
            @unlink($this->install->lockPath());

            return back()->withInput()->with('error', '安装失败：'.$e->getMessage());
        }

        $this->flashDone($request);

        return redirect()->route('install.done');
    }

    public function done(): View|RedirectResponse
    {
        $done = session('install_done');
        if (! is_array($done)) {
            return redirect($this->install->locked() ? '/admin/login' : '/install');
        }

        return view('install.done', [
            'email' => (string) ($done['email'] ?? ''),
            'demo' => (bool) ($done['demo'] ?? false),
            'siteName' => (string) ($done['site_name'] ?? 'CMS'),
        ]);
    }

    /** @return array<string, mixed> */
    protected function pageData(Request $request): array
    {
        $way = (string) $request->query('way', 'web');
        if (! in_array($way, ['web', 'laravel', 'docker', 'deploy'], true)) {
            $way = 'web';
        }
        $checks = $this->install->checks();
        $db = $this->install->databaseStatus();

        return [
            'way' => $way,
            'checks' => $checks,
            'requiredOk' => $this->install->requiredPassed($checks),
            'dbOk' => $db['ok'],
            'dbError' => $db['error'],
            'passwordMin' => 8,
        ];
    }

    protected function validateAccount(Request $request): void
    {
        $request->validate([
            'site_name' => 'required|string|max:80',
            'admin_name' => 'required|string|max:50',
            'admin_email' => 'required|email|max:120',
            'admin_password' => 'required|string|min:8|confirmed',
            'seed_demo' => 'nullable|boolean',
            'db_connection' => 'nullable|in:sqlite,mysql',
            'db_host' => 'nullable|string|max:120',
            'db_port' => 'nullable|string|max:8',
            'db_database' => 'nullable|string|max:120',
            'db_username' => 'nullable|string|max:120',
            'db_password' => 'nullable|string|max:200',
        ], [
            'site_name.required' => '请填写网站名称',
            'admin_name.required' => '请填写管理员姓名',
            'admin_email.required' => '请填写管理员邮箱',
            'admin_email.email' => '邮箱格式不对',
            'admin_password.required' => '请设置密码',
            'admin_password.min' => '密码至少 8 位',
            'admin_password.confirmed' => '两次密码不一致',
        ]);
    }

    /** @return array<string, mixed> */
    protected function payload(Request $request): array
    {
        return [
            'site_name' => (string) $request->input('site_name'),
            'admin_name' => (string) $request->input('admin_name'),
            'admin_email' => (string) $request->input('admin_email'),
            'admin_password' => (string) $request->input('admin_password'),
            'seed_demo' => $request->boolean('seed_demo'),
            'db_connection' => (string) $request->input('db_connection', ''),
            'db_host' => (string) $request->input('db_host', '127.0.0.1'),
            'db_port' => (string) $request->input('db_port', '3306'),
            'db_database' => (string) $request->input('db_database', ''),
            'db_username' => (string) $request->input('db_username', ''),
            'db_password' => (string) $request->input('db_password', ''),
            'app_url' => rtrim($request->getSchemeAndHttpHost(), '/'),
        ];
    }

    protected function flashDone(Request $request): void
    {
        session([
            'install_done' => [
                'email' => (string) $request->input('admin_email'),
                'demo' => $request->boolean('seed_demo'),
                'site_name' => (string) $request->input('site_name'),
            ],
        ]);
    }
}
