# Contributing

This is a Laravel 13 CMS. The admin UI is Chinese-first. Pull requests that add an app store, online updater, OAuth, payments, Filament, or a second template-tag dialect will be closed — those are explicit non-goals.

## Setup

PHP 8.4+, Composer, SQLite (or MySQL).

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan test
```

Docker: `docker compose up --build` then open http://127.0.0.1:8000/admin/login

## Tests

Feature tests live in `tests/Feature`. Name workbench UI tests `*WorkbenchTest.php`. Run the slice you touched:

```bash
php artisan test --filter=SideNavWorkbenchTest
```

CI runs the full suite. Do not skip tests to hide GD/SQLite gaps — declare the extension in the workflow instead.

## Code

- Keep Controller → Service → Model.
- Admin copy: Chinese names first, no jargon columns, no identical-status badges.
- Do not commit `.env`, backups, or `storage/app` uploads.
- Match surrounding style. If you use Pint locally: `vendor/bin/pint --dirty`.

## Events

Listen in `AppServiceProvider` (or a dedicated listener). Do not fork controllers for “when an article is published”.

| Event | When |
|-------|------|
| `App\Events\EntrySaved` | Entry saved (`$created`, `$publishedNow`) |
| `App\Events\CommentSubmitted` | New comment |
| `App\Events\CommentModerated` | Admin approved / rejected / spam |
| `App\Events\FormSubmitted` | Front form POST |
| `App\Events\ThemeActivated` | Theme slug changed |
| `App\Events\SettingSaved` | Settings keys written |

```php
use App\Events\EntrySaved;
use Illuminate\Support\Facades\Event;

Event::listen(EntrySaved::class, function (EntrySaved $e) {
    if ($e->publishedNow) {
        // ping a search engine, write a log, …
    }
});
```
