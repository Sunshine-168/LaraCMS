<?php

namespace Database\Seeders;

use App\Models\Admin\AdminRole;
use App\Models\Admin\AdminUser;
use App\Models\Cms\MemberGroup;
use App\Models\Cms\Setting;
use App\Models\Cms\Site;
use App\Support\CurrentSite;
use Illuminate\Database\Seeder;

/** 安装必跑：RBAC、默认站点、超管、基础设置。不含演示栏目/内容。 */
class AdminBootstrapSeeder extends Seeder
{
    public static string $siteName = 'CMS';

    /** @var array{name: string, email: string, password: string} */
    public static array $admin = [
        'name' => '超级管理员',
        'email' => 'admin@example.com',
        'password' => 'Admin@12345',
    ];

    public function run(): void
    {
        $this->call(AdminRbacSeeder::class);

        $site = Site::default();
        if (! $site) {
            $host = parse_url((string) config('app.url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
            $site = Site::query()->create([
                'name' => static::$siteName ?: '默认站点',
                'domain' => Site::normalizeDomain((string) $host),
                'is_default' => true,
                'is_active' => true,
            ]);
        } elseif (static::$siteName !== '') {
            $site->name = static::$siteName;
            $site->save();
        }
        CurrentSite::set($site);

        $superRoleId = AdminRole::query()->where('slug', 'super_admin')->value('id');
        AdminUser::query()->updateOrCreate(
            ['email' => static::$admin['email']],
            [
                'name' => static::$admin['name'],
                'password' => static::$admin['password'],
                'role_id' => $superRoleId,
                'is_active' => true,
            ]
        );

        MemberGroup::query()->updateOrCreate(
            ['site_id' => $site->id, 'slug' => 'default'],
            [
                'name' => '普通会员',
                'sort' => 0,
                'is_active' => true,
                'permissions' => MemberGroup::permissionKeys(),
            ]
        );

        $defaults = [
            'site_name' => static::$siteName ?: 'CMS',
            'site_description' => '',
            'site_keywords' => '',
            'theme' => 'enterprise',
            'mobile_theme' => 'mobile',
            'mobile_detect' => 'ua',
            'url_mode' => 'cat_entry',
            'comments_enabled' => '1',
            'comments_guest' => '1',
            'comments_auto_approve' => '0',
            'comments_rate_seconds' => '60',
        ];
        foreach ($defaults as $key => $value) {
            if (Setting::getValue($key) === null || Setting::getValue($key) === '') {
                Setting::setValue($key, $value);
            }
        }
        if (static::$siteName !== '') {
            Setting::setValue('site_name', static::$siteName);
        }
    }
}
