# TallCMS (larevel)

Modern multilingual CMS based on the official [TallCMS](https://tallcms.com) standalone project — **Laravel + Livewire + Alpine.js + Filament 5 + daisyUI**.

This directory replaces the previous HkCms Laravel migration. Old HkCms code was backed up to `../larevel_hkcms_legacy/`. The sibling ThinkPHP project (`../php/`) is unchanged.

## Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 13 |
| Admin | Filament 5 + TallCMS plugin |
| Frontend theme | TallDaisy (active) |
| i18n | 默认中文 `zh_cn`，另有 `en`（英文 URL `/en/...`） |
| 后台语言 | Filament `zh_CN` 语言包（`APP_LOCALE=zh_CN`） |
| Default DB | SQLite (`database/database.sqlite`) |

## Requirements

- PHP 8.2+ with extensions: `intl`, `zip`, `sodium`, `pdo_sqlite` (or `pdo_mysql`)
- Composer 2
- Node.js 20+

## Quick start

```bash
cd larevel
composer install
cp .env.example .env   # if needed; this repo already has a local .env
php artisan key:generate
# Ensure database/database.sqlite exists for SQLite, or configure MySQL (no hkcms_ prefix)
php artisan migrate --force
php artisan tallcms:install -n --force
php artisan tallcms:setup -n --force --name="Admin User" --email="admin@example.com" --password="password"
php artisan db:seed --class=Database\\Seeders\\CreateHomepageSeeder
php artisan storage:link
npm install && npm run build
php artisan serve
```

- 前台（中文默认）: http://127.0.0.1:8000/
- 英文前台: http://127.0.0.1:8000/en
- 后台: http://127.0.0.1:8000/admin

Default local admin (change after first login):

- Email: `admin@example.com`
- Password: `password`

## i18n configuration

In `.env`:

```env
APP_LOCALE=zh_CN
APP_FALLBACK_LOCALE=zh_CN
TALLCMS_I18N_ENABLED=true
TALLCMS_DEFAULT_LOCALE=zh_cn
TALLCMS_HIDE_DEFAULT_LOCALE=true
```

- 后台 UI：跟随 `APP_LOCALE=zh_CN`（Filament 自带简体中文包）
- 前台内容默认语言：`TALLCMS_DEFAULT_LOCALE=zh_cn`（根路径 `/` 为中文；英文在 `/en/...`）
- 导航/资源名中文：见 `config/tallcms.php` 的 `navigation`、`labels`

TallCMS 内部 locale 为小写下划线（`zh_cn`）；对外 URL 为 BCP-47（中文默认隐藏前缀，英文为 `/en/...`）。

## Themes

```bash
php artisan theme:list
php artisan theme:activate talldaisy
```

Docs: [Themes](https://tallcms.com/docs/themes) · [Installation](https://tallcms.com/docs/installation) · [i18n](https://tallcms.com/docs/i18n)

## Security hardening

项目已内置基础安全加固（见 `config/security.php`、`App\Http\Middleware\SecurityHeaders`）：

| 能力 | 说明 |
|------|------|
| 安全响应头 | `X-Content-Type-Options`、`X-Frame-Options`、`Referrer-Policy`、`Permissions-Policy`；HTTPS 时发 HSTS |
| Trust Proxies | `TRUST_PROXIES`（默认 `*`，生产建议改为具体网段） |
| HTTPS | `FORCE_HTTPS=true` 强制生成 https URL |
| 密码策略 | 默认最少 10 位、大小写+数字；非 local 环境检查泄露库 |
| 后台 MFA | `ADMIN_MFA_REQUIRED=true` 强制管理员二次验证 |
| 限流 | `login` / `api` 命名限流器 |
| Session | 建议 `SESSION_ENCRYPT=true`；生产 `SESSION_SECURE_COOKIE=true` |
| 安装器/API | 默认关闭：`INSTALLER_ENABLED=false`、`TALLCMS_API_ENABLED=false` |

生产环境建议：

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
FORCE_HTTPS=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
ADMIN_MFA_REQUIRED=true
LOG_LEVEL=error
INSTALLER_ENABLED=false
TALLCMS_API_ENABLED=false
```

部署后执行 `php artisan key:generate`（勿提交真实 `APP_KEY`），并尽快修改默认管理员密码、开启 MFA。

## Notes

- Do **not** point this app at old `hkcms_*` tables; TallCMS uses its own schema (`tallcms_*`).
- Frontend assets must be built (`npm run build`) or Filament admin will throw Vite manifest errors.
- Web installer is at `/install` only when `INSTALLER_ENABLED=true` and `installer.lock` is absent.
