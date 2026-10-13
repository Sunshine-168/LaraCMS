# LeafCMS

Laravel 13 建站 CMS：栏目、动态模型、推荐位、多站、会员、采集、静态化。前台只用 Blade **`@cms*`** 标签，不依赖 Filament，没有应用市场。

MIT 许可。二次开发按 Laravel 常规做（Service / Event / 主题目录），**不会**做在线升级、OAuth、支付，也不会引入第二套模板标签。见 [CONTRIBUTING](CONTRIBUTING.md)、[SECURITY](SECURITY.md)。内容保存、评论、表单、换主题会发出 `App\Events\*`，不必改核心文件。

## 安装

三种方式，装好后打开 http://127.0.0.1:8000/ 与 http://127.0.0.1:8000/admin/login。也可以打开 `/install` 在页面里切换「网页安装 / Laravel 命令行 / Docker」。

### Docker

```bash
docker compose up --build
```

启动时会复制 `.env`、迁移、导入演示数据。本地演示账号见下文，上线前必须改掉。

### Laravel 命令行

需要 PHP 8.2+ 和 Composer。默认 SQLite。

```bash
composer install
cp .env.example .env          # Windows: copy .env.example .env
php artisan key:generate
php artisan cms:install --email=you@example.com --password=YourPass#1
php artisan serve
```

加 `--demo` 会导入示例栏目和文章。MySQL 先改 `.env` 里的 `DB_*` 再执行 `cms:install`。

### 网页向导

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan serve
```

打开 http://127.0.0.1:8000/install ，检查环境、选数据库、填写管理员。不勾选演示数据也会创建你自己的超管。上线时的 PHP / MySQL / nginx 在安装页「上线部署」，装好后也可在后台「运营 → 缓存」底部查看。

**命令行一键种子（开发）**

```bash
php artisan migrate:fresh --seed
php artisan storage:link
```

- 本地种子：超管 `admin@example.com` / `Admin@12345`；编辑 `editor@example.com` / `Editor@12345`（无发布权）；运营 `operator@example.com` / `Operator@12345`；会员 `member@example.com` / `Member@12345`；认证会员 `vip@example.com` / `Vip@12345`
- 安装锁：`storage/app/install.lock`（删掉会再次进入 `/install`）

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

后台「系统 → 主题」切换当前站外观；点「用于本站」时会按主题包 **补上缺少的内容模型**（已有模型不改字段）并补空栏目，不会清掉原来的文章。也可在「设置」里换主题。「设置 → 移动外观」+ UA/域名分流可切到 `mobile`。体育主题仅作公开资讯/目录演示，请合法合规使用。

自己加主题不必改配置、也不用应用市场：

1. 后台「系统 → 主题」从现有主题复制一套，只改新文件夹
2. 或上传 zip（包内要有 `index/index.blade.php`，可带一层目录名当标识）
3. 或把目录放到 `resources/views/themes/{标识}/`，刷新主题页即出现
4. 命令：`php artisan cms:theme-make brand --from=enterprise --name=品牌站`

可选在主题根放 `theme.json`（`name` / `description` / `models` / `categories`）。预览图用 `preview.png`（或 `.jpg` / `.svg`），后台主题卡片会显示。至少要有 `index/index.blade.php` 才能点「用于本站」；缺页时前台会回落到企业主题。自带主题的模型定义在程序里，自己做的主题可在 `models/{标识}.json` 放一份后台「导出模型」同格式的 JSON。

## 本地说明

- 文档 ID 伪静态：`/e/{id}`（站点设置 `url_mode=entry_id`）
- 后台 ACL：角色权限 + 管理员栏目授权；侧栏按 `canPerm` 过滤
- 后台「系统 → 说明」：怎么用、后台各页、主题、模板、`@cms*` 标签；安装里有 PHP / MySQL / nginx、**定时任务和队列**、命令行、Docker
- 站点设置「更多」：再导入演示数据 / **整站备份**（或 `php artisan cms:backup`）。换主题后前台还是旧样子，在「运营 → 缓存」清空缓存。

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
| `@cmsMenu` / `@endcmsMenu` | 后台导航菜单（location=header；无菜单时回退栏目导航） |
| `@cmsContent` / `@endcmsContent` | 文档列表（catid/model/flag/page/filter；`related`+`related_field` 可跨栏目取关联文档） |
| `@cmsPaginate` | 分页 |
| `@cmsAdv` / `@endcmsAdv` | 推荐位 name=；条目可设 `html` 类型，主题循环里 `{!! $item->html !!}` |
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
| `/go/{entry}` | 外链点击计次；链接模型前台 URL 走此前缀（带语言前缀如 `/en/go/{id}`） |
| `/form/{slug}` | 表单 POST（验证码；后台收件箱可已读/备注/邮件回复） |
| `/comment/{entry}` | 评论提交（默认待审核；站点设置可开关/游客/限流/通知） |
| （后台配置） | 入站重定向：精确路径 301/302，命中计数，按站隔离 |
| `/preview/{token}` | 草稿/未到点发布预览（后台生成，默认 7 天；不计入浏览、不进 HTML 缓存） |
| `/search?q=` | 全站搜索（可按模型关闭 `is_searchable`；游客不含会员门槛文档） |
| `/member/login` | 会员登录（算术验证码 + 每分钟 5 次） |
| `/member/register` | 会员注册（算术验证码 + 每分钟 5 次） |
| `/member/forgot` | 找回密码（POST 每分钟 5 次；未配邮件时页面展示重置链接） |
| `/member/profile` | 会员中心（资料含手机/简介、改密、投稿、收藏、评论、留言、会员内容） |
| `/member/entries` | 我的投稿 / 写新稿 |
| `/tag/{slug}` | 标签聚合 |
| `/rss.xml` | RSS（不含会员门槛文档） |
| `/sitemap.xml` | 站点地图（XML，给搜索引擎） |
| `/sitemap` | 站点地图网页版（可在后台关掉） |

### 模型搬运 / 多栏目 / 磁盘静态

- **模型**：后台可复制、导出 JSON、导入 JSON（覆盖字段，不删文档）
- **多栏目**：主栏目决定 URL；附加栏目勾选后也会出现在对应栏目列表/详情解析中
- **磁盘 HTML**：默认关闭。后台打开并生成后写入 `public/html/site_{id}/…/index.html`。PHP 会直接吐这些文件；线上 nginx `try_files` 可拦在 PHP 前面更快。命令 `cms:html-build` / `cms:html-purge-disk`
- **语言前缀**：非默认语言走 `/en/...`。会员/下载/外链/表单/评论/RSS 用 `cms_route()` 指向带前缀的同名路由；隐藏默认语言时中文仍无前缀
- **会员密码**：最少 `PASSWORD_MIN_LENGTH`（默认 10）且需字母+数字；反向代理设 `TRUST_PROXIES`（默认空，不信任 `X-Forwarded-*`）
- **预览 / 版本**：编辑页可刷新草稿预览链接；每次保存快照上一稿（最多 20 条）并可还原
- **主题外观**：站点设置可上传 Logo / Favicon，填写主色、页脚文案、ICP 备案号、首页主/副标题。留空则沿用各主题自带配色与文案；企业/移动主题有 `home_banner` 推荐位时 Banner 优先于首页标题
- **上传水印**：站点设置可配文字/图片水印及位置（右下/左下/右上/左上/居中）；中文文字依赖系统 TTF，建议用透明 PNG
- **定时发布**：`published_at` 未到点时前台 `/e/{id}`、下载、外链跳转均为 404；后台列表显示「定时」
- **会员投稿**：模型勾选「允许会员投稿」+ 分组权限 `contribute`；会员保存草稿或提交待审，可填写该模型扩展字段；后台内容列表可筛待审并发布
- **编辑审核**：角色权限 `publish`（超管默认有，编辑角色无）。无此权限不能发布，也不能把已发布改回草稿/待审
- **CDN**：站点设置 `cdn_url`（如 `https://cdn.example.com`）后，正文 HTML、`cms_media()`、API 附件里的 `/storage` 与本站 APP_URL 前缀会改写到该域名；后台 `asset()` 静态资源（如 `css/admin.css`）不走 CDN
- **对象存储**：上传默认 `public` 盘。阿里云 OSS / 腾讯云 COS / S3 设 `CMS_MEDIA_DISK=s3` 并 `composer require league/flysystem-aws-s3-v3`，环境变量见后台「说明 → 环境」
- **整站备份**：站点设置「更多」或 `php artisan cms:backup`；后台可还原，或 `php artisan cms:backup --restore=cms-backup-YYYYMMDD-HHMMSS.zip`。zip 含 `database.sql`（CREATE TABLE + INSERT，sqlite 另附库文件）+ `uploads/`，不含 `.env`，保留最近 5 份。还原按当前表结构写入数据，不能用来升级/降级数据库结构
- **站点地图**：后台「运营 → 更多 → 地图」可勾栏目 / 文章 / 标签、限制文章条数、加补充地址。会员门槛文档不列入。XML `/sitemap.xml`（含 lastmod / changefreq / priority），网页版 `/sitemap`。`robots.txt` 列出各语言 Sitemap（如 `/en/sitemap.xml`）

## REST API

默认 **GET 公开只读**（可开 Token 校验）。`POST` 写入在携带有效站点 Token 时可免验证码；无 Token 时仍走会话验证码。会员登录限流每分钟 5 次；带站点 Token 时可免登录验证码。会员接口另需登录后下发的 `Authorization: Bearer`（或 `X-Member-Token`），不依赖 Sanctum。

响应格式：`{ "code": 200, "msg": "ok", "data": ... }`

- 站点 Header：`X-CMS-Token: <token>`
- 或 Query：`?api_token=<token>`
- 会员 Header：`Authorization: Bearer mbr_…` 或 `X-Member-Token`

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/api/site/config` | 站点名/主题/语言/url_mode/外观（logo_url、主色、ICP、首页文案） |
| GET | `/api/category/list?limit=&model_id=&parent_id=` | 栏目分页 |
| GET | `/api/category/tree` | 栏目树 |
| GET | `/api/category/{id}` | 栏目详情 |
| GET | `/api/content/list?limit=&category_id=&model_id=&flag=&q=` | 内容分页（游客不含会员门槛） |
| GET | `/api/content/{id}` | 内容详情（含正文/attrs；会员门槛需会员令牌） |
| GET | `/api/search?q=` | 内容搜索（仅 `is_searchable`；游客不含会员门槛） |
| GET | `/api/tag/list` | 标签分页 |
| GET | `/api/tag/{slug}` | 标签及文档列表（游客不含会员门槛） |
| GET | `/api/recommend?name=&num=` | 推荐位（含查询型） |
| POST | `/api/form/{slug}` | 提交表单（字段同前台） |
| POST | `/api/comment/{id}` | 提交评论 |
| POST | `/api/member/login` | 会员登录（限流；返回一次性 token） |
| GET | `/api/member/me` | 当前会员资料 |
| PUT | `/api/member/profile` | 更新昵称/头像/手机/简介 |
| POST | `/api/member/logout` | 注销会员令牌 |
| GET | `/api/member/favorites` | 收藏列表 |
| POST | `/api/member/favorite/{id}` | 收藏/取消收藏 |
| GET | `/api/member/entries` | 我的投稿 |
| GET | `/api/member/entries/{id}` | 投稿详情（含正文） |
| POST | `/api/member/entries` | 新建投稿（`submit=1` 直接待审） |
| PUT | `/api/member/entries/{id}` | 更新草稿 |
| POST | `/api/member/entries/{id}/submit` | 提交审核 |

## 定时采集（RSS / JSON / HTML）

后台「采集」可配置公开 RSS/Atom、JSON 列表，或 **HTML 选择器**（列表页条目 + 可选同源详情正文）。按间隔写入文档（`source_guid` 去重）。HTML 模式不翻页、不爬全站，并拒绝内网地址。仅采集你有权使用的公开源。

## 多站点（全站租户）

共享同一数据库与进程：按 **域名** 解析站点，栏目/内容/会员/设置等均带 `site_id` 隔离。

- 后台「系统 → 站点管理」维护域名；顶栏切换当前编辑站
- 超管可切全部站点；普通管理员在「管理员」里勾选可管理站点
- 新建站点会复制默认站的基础设置 / 语言 / Flags / 会员组，**不复制内容**
- 本地多域名可用 hosts，例如：`127.0.0.1 site-a.test site-b.test`
- `local` / `testing` 环境未知域名回落默认站；生产未知域名返回 404
- 前台 API 同样按 `Host` 解析（`/api/site/config` 含 `site_id` / `site_domain`）

### 采集与调度运行模式

| `CMS_COLLECT_MODE` | 行为 | 需要进程 |
|---|---|---|
| `schedule`（默认） | `schedule:run` 里**同步**跑采集 | cron / `schedule:work` |
| `queue` | 调度只**投递 Job**，worker 消费 | 轻量 cron + `queue:work` |
| `off` | 不自动跑 | 仅手动 / 后台按钮 |

```env
CMS_SCHEDULE_ENABLED=true
CMS_COLLECT_MODE=queue          # 或 schedule / off
CMS_COLLECT_QUEUE=cms
QUEUE_CONNECTION=database       # 或 redis；勿用 sync 若真要异步
```

**模式 A：纯定时**
```bash
* * * * * cd /var/www/cms && php artisan schedule:run
```

**模式 B：队列（推荐）**
```bash
* * * * * cd /var/www/cms && php artisan schedule:run
php artisan queue:work --queue=cms --sleep=1 --tries=1
```

**模式 C：外部 cron 直接打命令（可关 Laravel 调度）**
```env
CMS_SCHEDULE_ENABLED=false
```
```bash
* * * * * cd /var/www/cms && php artisan cms:collect --queue
# 或同步：
* * * * * cd /var/www/cms && php artisan cms:collect --sync
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
- 环境变量：`CMS_HTML_CACHE=true`、`CMS_HTML_CACHE_TTL=3600`；登录/注册算术验证码 `CMS_AUTH_CAPTCHA`（默认开）；磁盘静态 `CMS_DISK_HTML`（默认关）
- 命令：`php artisan cms:html-clear` / `php artisan cms:html-warm --entries=30`
- 不缓存：后台、会员、表单、下载、外链跳转、搜索、预览、已登录会员
- 响应头：`X-CMS-Html-Cache: HIT|MISS`

默认用 CACHE 驱动的 epoch 全站失效；Redis/Memcached 时可按标签精细失效。

## 明确不做

前台模板标签只有 `@cms*`。下列能力也**不会**做：

- 应用中心 / 在线升级、运行时按模型建物理表、Filament、旧库数据导入、遗留 `cms_pages` / `cms_posts` 表
- 自由伪静态规则矩阵、短信、微信/OAuth 登录、支付
- Webhooks、Scout 全文检索、作者归档页

会员权限、前台投稿（待审后发布）、表单、评论、收藏、采集、统计、多站点、缓存、预览/修订、CDN 资源域名、整站备份均已在本目录实现。
