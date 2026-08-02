<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## HkCms Laravel CMS 说明

`larevel` 在兼容旧 `hkcms_*` 库表的基础上，提供现代前台主题与 **Blade 指令标签**（不兼容旧 ThinkPHP `{hkcms:}` 主题 HTML）。

已具备：

- 模型/后台/API：栏目、内容、推荐位、站点配置、附件等
- 前台路由：`/`、`/{catname}`、`/{catname}/{id}`
- 主题引擎：`resources/views/themes/{theme}/`，由站点配置 `index_theme` 或 `CMS_THEME` 决定
- 模板标签：`@cmsChannel`、`@cmsContent`、`@cmsSeo` 等 Blade 指令

## 启动前需要配置

请把 Laravel 的数据库连接指向 HkCms 数据库，并确保表前缀是 `hkcms_`。

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=hkcms
DB_USERNAME=root
DB_PASSWORD=
DB_PREFIX=hkcms_
CMS_DEFAULT_LOCALE=zh-cn
CMS_THEME=default
```

```bash
php artisan serve
```

## 主题约定

视图命名空间：`cms_theme`（指向 `resources/views/themes/{theme}`，缺省回退 `default`）。

| 页面 | 解析规则 | 默认视图 |
|------|----------|----------|
| 首页 | 固定 | `index/index` |
| 栏目频道 `type=category` | `category/{category_tpl}` | `category/category` |
| 列表 `type=list` | `list/{list_tpl}` | `list/list` |
| 详情 | `show/{show_tpl}`（文章优先，否则栏目） | `show/show` |

`*_tpl` 可写 `list` / `list.html` / `list.blade.php`，会规范化后解析。

默认主题目录：

```
resources/views/themes/default/
  layouts/app.blade.php
  partials/{header,footer,paginate}.blade.php
  index/index.blade.php
  category/category.blade.php
  list/list.blade.php
  show/show.blade.php
```

## Blade 指令标签

循环变量默认 `$item`，可用 `'as' => 'nav'` 改名。参数为 PHP 数组。

```blade
@cmsSeo
@cmsBreadcrumb(['symbol' => ' / '])

@cmsChannel(['type' => 'top', 'ismenu' => 1, 'num' => 10])
  <a href="{{ $item->url }}">{{ $item->title }}</a>
@endcmsChannel

@cmsContent(['num' => 10, 'page' => true, 'order' => 'weigh desc,id desc'])
  <a href="{{ $item->url }}">{{ $item->title }}</a>
@endcmsContent
@cmsPaginate

@cmsAdv(['name' => 'home_banner'])
  <img src="{{ $item->image }}" alt="{{ $item->title }}">
@endcmsAdv

@cmsDate(['value' => $item->publish_time, 'format' => 'Y-m-d'])
@cmsSubstr(['value' => $item->title, 'len' => 40, 'dot' => '...'])
@cmsPrev(['len' => 20]) / @cmsNext(['len' => 20])
```

| 指令 | 说明 | 常用参数 |
|------|------|----------|
| `@cmsSeo` | 输出 title/keywords/description | — |
| `@cmsChannel` / `@endcmsChannel` | 栏目列表 | `type`=`top\|son\|peer\|selftop\|selfson`，`name`，`ismenu`，`num`，`model`，`as` |
| `@cmsContent` / `@endcmsContent` | 文档列表 | `catid`，`num`，`page`，`order`，`insub`，`model`，`aid(s)`，`as` |
| `@cmsPaginate` | 渲染最近一次分页的 `@cmsContent` | 可选自定义视图名 |
| `@cmsAdv` / `@endcmsAdv` | 推荐位/Banner | `name`，`num`，`itemid`，`as` |
| `@cmsBreadcrumb` | 面包屑 | `symbol`，`class`，`catid` |
| `@cmsArcone` / `@endcmsArcone` | 单篇 | `aid`，`model`，`as` |
| `@cmsPrev` / `@cmsNext` | 上一篇/下一篇链接 | `len`，`dot`，`msg`，`catid` |
| `@cmsDate` / `@cmsSubstr` | 格式化 | `value`/`name`，`format`/`len`/`dot`，`api=human` |

缺省 `catid` 取当前栏目（由 `CmsViewContext` 注入）。列表类标签在主题中发起查询，控制器只负责路由解析与 SEO 上下文。

## 当前范围外

- 旧 `{hkcms:}` 语法兼容
- `@cmsFilter` / `@cmsForm` / `@cmsQuery` / 标签云
- 会员中心、完整插件 taglib 热加载

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
