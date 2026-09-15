<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Database\Seeders\AdminBootstrapSeeder;
use Database\Seeders\CmsSeeder;
use Database\Seeders\DemoDataSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class InstallController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (is_file(storage_path('app/install.lock'))) {
            return redirect('/admin/login');
        }

        $checks = [
            'php' => version_compare(PHP_VERSION, '8.2.0', '>='),
            'pdo' => extension_loaded('pdo'),
            'mbstring' => extension_loaded('mbstring'),
            'openssl' => extension_loaded('openssl'),
            'storage' => is_writable(storage_path()),
            'env' => is_file(base_path('.env')),
        ];

        $dbOk = false;
        $dbError = null;
        try {
            DB::connection()->getPdo();
            $dbOk = true;
        } catch (\Throwable $e) {
            $dbError = $e->getMessage();
        }

        return view('install.index', compact('checks', 'dbOk', 'dbError'));
    }

    public function run(Request $request): RedirectResponse
    {
        if (is_file(storage_path('app/install.lock'))) {
            return redirect('/admin/login');
        }

        $request->validate([
            'site_name' => 'required|string|max:80',
            'admin_name' => 'required|string|max:50',
            'admin_email' => 'required|email|max:120',
            'admin_password' => 'required|string|min:8|confirmed',
            'seed_demo' => 'nullable|boolean',
        ]);

        try {
            Artisan::call('migrate', ['--force' => true]);
            AdminBootstrapSeeder::$siteName = (string) $request->input('site_name');
            AdminBootstrapSeeder::$admin = [
                'name' => (string) $request->input('admin_name'),
                'email' => (string) $request->input('admin_email'),
                'password' => (string) $request->input('admin_password'),
            ];
            Artisan::call('db:seed', ['--class' => AdminBootstrapSeeder::class, '--force' => true]);
            if ($request->boolean('seed_demo')) {
                Artisan::call('db:seed', ['--class' => CmsSeeder::class, '--force' => true]);
                Artisan::call('db:seed', ['--class' => DemoDataSeeder::class, '--force' => true]);
            }
            file_put_contents(
                storage_path('app/install.lock'),
                'installed_at='.now()->toDateTimeString()."\nseed_demo=".($request->boolean('seed_demo') ? '1' : '0')."\n"
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', '安装失败：'.$e->getMessage());
        }

        return redirect('/admin/login')->with('status', '安装完成，请使用刚才填写的管理员账号登录');
    }
}
