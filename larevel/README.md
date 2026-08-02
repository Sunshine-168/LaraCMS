# 综合 Laravel CMS（JYS 分层 + Blade 模板标签）

Controller → Service → Model。能力对标 HkCms「栏目 + 动态模型 + 推荐位 + 主题」，前台用 **Blade `@cms*` 标签**，不依赖 Filament / `{hkcms:}`，不迁旧库数据。

## 站点形态（主题包）

同一套内核，靠 **内容模型 + 主题包** 切换：

| 主题 | 形态 | 关键模型 |
|------|------|----------|
| `enterprise` | 企业站 | article / page / product / job |
| `mobile` | 移动端皮肤 | 同上（可作 mobile_theme） |
| `download` | 下载站 | download |
| `nav` | BC 导航站 | link |
| `source` | 源码站 | source |
| `blog` | 简单博客 | article |
| `manga` | 漫画站 | manga / chapter |
| `sports` | 体育资讯/信号目录 | signal |

后台「设置 → 主题」切换 PC 主题；「移动主题」+ UA/域名分流可切到 `mobile`。体育主题仅作公开资讯/目录演示，请合法合规使用。

## 快速开始

**方式 A：安装向导**
1. 配好 `.env` 后 `composer install && php artisan key:generate`
2. 浏览器打开 `/install`，勾选演示数据并安装

**方式 B：命令行**
```bash
cd larevel
composer install
cp .env.example .env   # 按需
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

- 前台：http://127.0.0.1:8000/
- 英文：http://127.0.0.1:8000/en
- 文档 ID 伪静态：`/e/{id}`（站点设置 `url_mode=entry_id`）
- 后台：http://127.0.0.1:8000/admin/login  
- 超管：`admin@example.com` / `Admin@12345`（`is_super`，全部权限）
- 编辑：`editor@example.com` / `Editor@12345`（内容模块权限，无模型/设置/角色管理）
- 前台会员：`member@example.com` / `Member@12345`（会员门槛下载）
- 后台 ACL：角色权限 + 管理员栏目授权；侧栏按 `canPerm` 过滤
- 系统工具：清缓存 / 再导入演示数据（`admin/cache`）
- 安装锁：`storage/app/install.lock`（删除后会再次进入 `/install`）

## 目录

```
app/Http/Controllers/{Admin,Web}
app/Services/{Admin,Cms,Cms/Tags}
app/Models/{Admin,Cms}
app/Cms/Blade          # @cms* 注册
resources/views/admin
resources/views/themes/{enterprise,mobile,download,nav,source,blog,manga,sports}
```

## 模板标签（P0）

| 指令 | 作用 |
|------|------|
| `@cmsSeo` | title / keywords / description |
| `@cmsChannel` / `@endcmsChannel` | 栏目列表 type=top\|son\|peer |
| `@cmsContent` / `@endcmsContent` | 文档列表（catid/model/flag/page/filter） |
| `@cmsPaginate` | 分页 |
| `@cmsAdv` / `@endcmsAdv` | 推荐位 name= |
| `@cmsArcone` | 单条 |
| `@cmsBreadcrumb` | 面包屑 |
| `@cmsPrev` / `@cmsNext` | 上下篇 |
| `@cmsDate` / `@cmsSubstr` | 格式化 |
| `@cmsFilter` / `@cmsOrder` | 筛选 / 排序链接 |
| `@cmsFileinfo` | 附件信息 |
| `@cmsForm` / `@cmsFormFields` | 完整表单（含验证码）/ 字段循环 |
| `@cmsTaglist` | 标签云 |
| `@cmsMap` | OpenStreetMap 嵌入 |

示例：

```blade
@cmsChannel(['type' => 'top', 'ismenu' => 1])
    <a href="{{ $item->frontUrl() }}">{{ $item->t('title') }}</a>
@endcmsChannel

@cmsContent(['model' => 'article', 'num' => 6])
    <a href="{{ $item->url }}">{{ $item->t('title') }}</a>
@endcmsContent

@cmsAdv(['name' => 'home_banner'])
    <img src="{{ $item->image }}" alt="{{ $item->t('title') }}">
@endcmsAdv
```

循环变量默认 `$item`，可用 `'as' => 'cat'` 改名（嵌套循环时必须）。

## 路由

| 路径 | 说明 |
|------|------|
| `/` | 主题首页 |
| `/{slug}` | 栏目（list/single/hub/link） |
| `/{cat}/{slug}` | 文档详情（默认伪静态） |
| `/e/{id}` | 文档详情（`url_mode=entry_id`） |
| `/c/{id}` `/c/{id}/{slug}` | 栏目/文档（`url_mode=cat_id`） |
| `/{model}/{cat}/{slug}` | 文档（`url_mode=model_cat_entry`） |
| `/api/*` | REST API（见下方） |
| `/install` | 安装向导（无 install.lock 时） |
| `/download/{entry}` | 下载计次 |
| `/go/{entry}` | 外链点击计次 |
| `/form/{slug}` | 表单 POST（验证码） |
| `/search?q=` | 全站搜索 |
| `/member/login` | 会员登录 |
| `/member/register` | 会员注册 |
| `/member/forgot` | 找回密码（未配邮件时页面展示重置链接） |
| `/member/profile` | 会员中心（改密） |
| `/tag/{slug}` | 标签聚合 |
| `/rss.xml` | RSS |
| `/sitemap.xml` | 站点地图 |

## REST API

默认**公开只读**。站点设置开启「API Token 校验」后，请求需带：

- Header：`X-CMS-Token: <token>`
- 或 Query：`?api_token=<token>`

响应格式：`{ "code": 200, "msg": "ok", "data": ... }`

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/site/config` | 站点名/主题/语言/url_mode |
| GET | `/api/category/list?limit=&model_id=&parent_id=` | 栏目分页 |
| GET | `/api/category/tree` | 栏目树 |
| GET | `/api/category/{id}` | 栏目详情 |
| GET | `/api/content/list?limit=&category_id=&model_id=&flag=&q=` | 内容分页 |
| GET | `/api/content/{id}` | 内容详情（含正文/attrs） |
| GET | `/api/recommend?name=&num=` | 推荐位（含查询型） |

## 定时采集（RSS / JSON）

后台「采集」可配置公开 RSS/Atom 或 JSON 列表源，按间隔写入文档（`source_guid` 去重）。仅采集你有权使用的公开源，勿用于未授权整站抓取。

### 运行模式（多站点可切换）

每个站点用 `.env` 独立配置，互不影响：

| `CMS_COLLECT_MODE` | 行为 | 需要进程 |
|---|---|---|
| `schedule`（默认） | `schedule:run` 里**同步**跑采集 | 每站 cron / `schedule:work` |
| `queue` | 调度只**投递 Job**，worker 消费 | 每站轻量 cron + 共用 `queue:work` |
| `off` | 不自动跑 | 仅手动 / 后台按钮 |

```env
CMS_SCHEDULE_ENABLED=true
CMS_COLLECT_MODE=queue          # 或 schedule / off
CMS_COLLECT_QUEUE=cms
QUEUE_CONNECTION=database       # 或 redis；勿用 sync 若真要异步
```

**模式 A：纯定时（站点少 / 无 Redis）**
```bash
# 每站一条 cron
* * * * * cd /var/www/site1 && php artisan schedule:run
```

**模式 B：队列（站点多，推荐）**
```bash
# 每站仍要轻量 schedule:run（只负责 dispatch，几乎不占 CPU）
* * * * * cd /var/www/site1 && php artisan schedule:run
* * * * * cd /var/www/site2 && php artisan schedule:run

# 共用 worker（可一台机跑多个）
php artisan queue:work --queue=cms --sleep=1 --tries=1
```

**模式 C：外部 cron 直接打命令（可关 Laravel 调度）**
```env
CMS_SCHEDULE_ENABLED=false
```
```bash
* * * * * cd /var/www/site1 && php artisan cms:collect --queue
# 或同步：
* * * * * cd /var/www/site1 && php artisan cms:collect --sync
```

```bash
# 手动（可临时覆盖模式）
php artisan cms:collect
php artisan cms:collect --force
php artisan cms:collect --id=1 --force
php artisan cms:collect --queue
php artisan cms:collect --sync
```

各采集器用「间隔（分钟）」决定是否真正拉取。Seed 含「Laravel Blog RSS」示例（默认停用）。统计清理同样支持 `CMS_STAT_PRUNE_MODE` / `--queue` / `--sync`。

## 流量 / 蜘蛛统计

前台 GET 访问自动写入 `cms_stat_hits`（后台路径与静态资源不记）。识别百度/Google/Bing/搜狗/360/Bytespider 等常见蜘蛛。

- 后台「统计」：今日/昨日/7/30 天 PV·UV·IP、14 日趋势、热门页、来路
- 「蜘蛛」：按蜘蛛汇总、按日、最近抓取明细
- 清理：`php artisan cms:stat-prune --days=90`（调度每天 03:20）

## 全页 HTML 缓存（静态化）

不改 URL、不写 `public/html/`。命中后直接返回整页 HTML；内容/栏目/推荐/设置/采集变更后自动失效。

- 后台「全页缓存」：开关、TTL、清空、预热
- 环境变量：`CMS_HTML_CACHE=true`、`CMS_HTML_CACHE_TTL=3600`
- 命令：`php artisan cms:html-clear` / `php artisan cms:html-warm --entries=30`
- 不缓存：后台、会员、表单、下载、外链跳转、搜索、已登录会员
- 响应头：`X-CMS-Html-Cache: HIT|MISS`

默认用 CACHE 驱动的 epoch 全站失效；Redis/Memcached 时可按标签精细失效。

## 与 HkCms / TallCMS

- **迁**：栏目类型、动态模型（JSON attrs）、推荐位、主题 list/show、Blade 标签查询层、定时采集、流量/蜘蛛统计、全页 HTML 缓存  
- **不迁**：`{hkcms:}`、磁盘整站静态 HTML、运行时建表、App Center、Filament 区块编辑器、旧 `hkcms_*` 数据

旧目录：`larevel_tallcms_legacy`、`php/`（HkCms）仅作参考。
