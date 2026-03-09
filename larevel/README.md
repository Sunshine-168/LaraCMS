<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## HkCms Laravel 迁移说明

当前这个 `larevel` 目录已经接入了旧 `php` 项目的前台核心逻辑，完成了下面几部分的第一版迁移：

- 旧库表兼容模型：`config`、`category`、`model`、`archives`
- 前台路由：`/`、`/{catname}`、`/{catname}/{id}`
- 前台页面：首页、栏目列表、内容详情
- 站点配置读取：从旧表 `hkcms_config` 读取站点标题、SEO、URL 格式等配置

## 启动前需要配置

请把 Laravel 的数据库连接指向旧 HkCms 数据库，并确保表前缀是 `hkcms_`。

推荐至少配置这些环境变量：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hkcms
DB_USERNAME=root
DB_PASSWORD=
DB_PREFIX=hkcms_
CMS_DEFAULT_LOCALE=zh-cn
```

然后启动 Laravel：

```bash
php artisan serve
```

## 当前范围

这次迁移的是“前台主链路”，主要用于先跑通旧数据：

- 首页内容聚合
- 栏目列表访问
- 文章详情访问

还没有迁移的内容：

- 后台管理
- API 接口
- 模板标签系统
- 会员中心
- 插件和扩展机制
- 旧模板的完整 HTML/CSS 还原

## 已迁移插件

### `address`

兼容入口：

- 旧路径：`/addons/address/index/index`
- Laravel Blade 组件：`x-addons.address-picker`
- Laravel Blade 组件：`x-addons.address-map`

环境变量：

```env
ADDONS_ADDRESS_MAP_TYPE=1
ADDONS_ADDRESS_MAP_AK=你的地图AK
```

示例：

```blade
<x-addons.address-picker
    point-name="company_point"
    address-name="company_address"
/>

<x-addons.address-map
    id="company-map"
    title="公司位置"
    address="广州市天河区"
    :point="['lng' => 113.32459, 'lat' => 23.10668]"
/>
```

### `simditor`

已迁移内容：

- 静态资源：`public/static/addons/simditor`
- 兼容上传接口：`/common/upload`
- Laravel Blade 组件：`x-addons.simditor`

示例：

```blade
<x-addons.simditor
    name="content"
    id="content-editor"
    :value="$content ?? ''"
/>
```

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
