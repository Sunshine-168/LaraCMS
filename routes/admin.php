<?php

use App\Http\Controllers\Admin\AdminLogController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\CacheController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CollectorController;
use App\Http\Controllers\Admin\CommentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EntryController;
use App\Http\Controllers\Admin\FlagController;
use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\Admin\FormSubmissionController;
use App\Http\Controllers\Admin\HtmlCacheController;
use App\Http\Controllers\Admin\LocaleController;
use App\Http\Controllers\Admin\MediaController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MemberGroupController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\ModelController;
use App\Http\Controllers\Admin\QuickPanelController;
use App\Http\Controllers\Admin\RecommendController;
use App\Http\Controllers\Admin\RecycleController;
use App\Http\Controllers\Admin\RedirectController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\Admin\StatController;
use App\Http\Controllers\Admin\TagController;
use App\Http\Controllers\Admin\ThemeController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.submit');

    Route::middleware(['admin.auth', 'admin.log'])->group(function () {
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        Route::post('site/switch', [SiteController::class, 'switch'])->name('site.switch');
        Route::get('/', [DashboardController::class, 'index'])->middleware('admin.perm:dashboard')->name('dashboard');
        Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('admin.perm:dashboard')->name('dashboard.alt');
        Route::middleware('admin.perm:dashboard')->group(function () {
            Route::get('quick-panels', [QuickPanelController::class, 'edit'])->name('quick-panels.edit');
            Route::put('quick-panels', [QuickPanelController::class, 'update'])->name('quick-panels.update');
        });

        Route::middleware('admin.perm:sites')->group(function () {
            Route::post('sites/{site}/toggle', [SiteController::class, 'toggle'])->name('sites.toggle');
            Route::post('sites/{site}/default', [SiteController::class, 'makeDefault'])->name('sites.default');
            Route::resource('sites', SiteController::class)->except(['show']);
        });

        Route::middleware('admin.perm:entries')->group(function () {
            Route::post('entries/{entry}/preview', [EntryController::class, 'issuePreview'])->name('entries.preview')->whereNumber('entry');
            Route::post('entries/{entry}/revisions/{revision}/restore', [EntryController::class, 'restoreRevision'])->name('entries.revisions.restore')->whereNumber('entry');
            Route::post('entries/batch', [EntryController::class, 'batch'])->name('entries.batch');
            Route::resource('entries', EntryController::class)->except(['show'])->whereNumber('entry');
        });
        Route::middleware('admin.perm:recycle')->group(function () {
            Route::get('recycle', [RecycleController::class, 'index'])->name('recycle.index');
            Route::post('recycle/empty', [RecycleController::class, 'empty'])->name('recycle.empty');
            Route::post('recycle/batch', [RecycleController::class, 'batch'])->name('recycle.batch');
            Route::post('recycle/{entry}/restore', [RecycleController::class, 'restore'])->name('recycle.restore')->whereNumber('entry');
            Route::delete('recycle/{entry}', [RecycleController::class, 'forceDestroy'])->name('recycle.force')->whereNumber('entry');
        });
        Route::middleware('admin.perm:categories')->group(function () {
            Route::resource('categories', CategoryController::class)->except(['show']);
        });
        Route::middleware('admin.perm:tags')->group(function () {
            Route::resource('tags', TagController::class)->except(['show']);
        });
        Route::middleware('admin.perm:flags')->group(function () {
            Route::post('flags/{flag}/toggle', [FlagController::class, 'toggle'])->name('flags.toggle');
            Route::post('flags/{flag}/move', [FlagController::class, 'move'])->name('flags.move');
            Route::resource('flags', FlagController::class)->except(['show']);
        });
        Route::middleware('admin.perm:locales')->group(function () {
            Route::post('locales/{locale}/toggle', [LocaleController::class, 'toggle'])->name('locales.toggle');
            Route::post('locales/{locale}/default', [LocaleController::class, 'makeDefault'])->name('locales.default');
            Route::resource('locales', LocaleController::class)->except(['show']);
        });
        Route::middleware('admin.perm:forms')->group(function () {
            Route::get('forms', [FormController::class, 'index'])->name('forms.index');
            Route::get('forms/create', [FormController::class, 'create'])->name('forms.create');
            Route::post('forms', [FormController::class, 'store'])->name('forms.store');
            Route::post('forms/{form}/toggle', [FormController::class, 'toggle'])->name('forms.toggle');
            Route::get('forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
            Route::put('forms/{form}', [FormController::class, 'update'])->name('forms.update');
            Route::delete('forms/{form}', [FormController::class, 'destroy'])->name('forms.destroy');
        });
        Route::middleware('admin.perm:form_submissions')->group(function () {
            Route::get('form-submissions', [FormSubmissionController::class, 'index'])->name('form-submissions.index');
            Route::get('form-submissions/export', [FormSubmissionController::class, 'export'])->name('form-submissions.export');
            Route::get('form-submissions/{submission}', [FormSubmissionController::class, 'show'])->name('form-submissions.show');
            Route::put('form-submissions/{submission}', [FormSubmissionController::class, 'update'])->name('form-submissions.update');
            Route::post('form-submissions/{submission}/unread', [FormSubmissionController::class, 'unread'])->name('form-submissions.unread');
            Route::delete('form-submissions/{submission}', [FormSubmissionController::class, 'destroy'])->name('form-submissions.destroy');
        });
        Route::middleware('admin.perm:comments')->group(function () {
            Route::get('comments', [CommentController::class, 'index'])->name('comments.index');
            Route::post('comments/batch', [CommentController::class, 'batch'])->name('comments.batch');
            Route::post('comments/{comment}/moderate', [CommentController::class, 'moderate'])->name('comments.moderate');
            Route::delete('comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
        });
        Route::middleware('admin.perm:redirects')->group(function () {
            Route::post('redirects/{redirect}/toggle', [RedirectController::class, 'toggle'])->name('redirects.toggle');
            Route::resource('redirects', RedirectController::class)->except(['show']);
        });
        Route::middleware('admin.perm:menus')->group(function () {
            Route::post('menus/{menu}/toggle', [MenuController::class, 'toggle'])->name('menus.toggle');
            Route::resource('menus', MenuController::class)->except(['show']);
        });
        Route::middleware('admin.perm:media,entries')->group(function () {
            Route::get('media/json', [MediaController::class, 'json'])->name('media.json');
            Route::post('media/upload', [MediaController::class, 'upload'])->name('media.upload');
        });
        Route::middleware('admin.perm:media')->group(function () {
            Route::get('media', [MediaController::class, 'index'])->name('media.index');
            Route::post('media', [MediaController::class, 'store'])->name('media.store');
            Route::delete('media/{medium}', [MediaController::class, 'destroy'])->name('media.destroy');
        });
        Route::middleware('admin.perm:stats')->group(function () {
            Route::get('stats', [StatController::class, 'index'])->name('stats.index');
            Route::get('stats/export', [StatController::class, 'export'])->name('stats.export');
            Route::get('stats/spiders', [StatController::class, 'spiders'])->name('stats.spiders');
            Route::get('stats/spiders/export', [StatController::class, 'exportSpiders'])->name('stats.spiders.export');
            Route::get('stats/logs', [StatController::class, 'logs'])->name('stats.logs');
            Route::get('stats/logs/export', [StatController::class, 'exportLogs'])->name('stats.logs.export');
        });
        Route::middleware('admin.perm:models')->group(function () {
            Route::get('models', [ModelController::class, 'index'])->name('models.index');
            Route::get('models/create', [ModelController::class, 'create'])->name('models.create');
            Route::post('models', [ModelController::class, 'store'])->name('models.store');
            Route::get('models/import', [ModelController::class, 'importForm'])->name('models.import');
            Route::post('models/import', [ModelController::class, 'import'])->name('models.import.submit');
            Route::get('models/{model}/edit', [ModelController::class, 'edit'])->name('models.edit');
            Route::put('models/{model}', [ModelController::class, 'update'])->name('models.update');
            Route::delete('models/{model}', [ModelController::class, 'destroy'])->name('models.destroy');
            Route::post('models/{model}/copy', [ModelController::class, 'copy'])->name('models.copy');
            Route::get('models/{model}/export', [ModelController::class, 'export'])->name('models.export');
            Route::get('models/{model}/fields/create', [ModelController::class, 'createField'])->name('models.fields.create');
            Route::post('models/{model}/fields', [ModelController::class, 'storeField'])->name('models.fields.store');
            Route::get('models/{model}/fields/{field}/edit', [ModelController::class, 'editField'])->name('models.fields.edit');
            Route::put('models/{model}/fields/{field}', [ModelController::class, 'updateField'])->name('models.fields.update');
            Route::delete('models/fields/{field}', [ModelController::class, 'destroyField'])->name('models.fields.destroy');
        });
        Route::middleware('admin.perm:recommends')->group(function () {
            Route::post('recommends/{recommend}/toggle', [RecommendController::class, 'toggle'])->name('recommends.toggle');
            Route::resource('recommends', RecommendController::class)->except(['show']);
        });
        Route::middleware('admin.perm:members')->group(function () {
            Route::post('members/{member}/toggle', [MemberController::class, 'toggle'])->name('members.toggle');
            Route::resource('members', MemberController::class)->except(['show']);
        });
        Route::middleware('admin.perm:member_groups')->group(function () {
            Route::post('member-groups/{member_group}/toggle', [MemberGroupController::class, 'toggle'])->name('member-groups.toggle');
            Route::post('member-groups/{member_group}/move', [MemberGroupController::class, 'move'])->name('member-groups.move');
            Route::resource('member-groups', MemberGroupController::class)->except(['show']);
        });
        Route::middleware('admin.perm:collectors')->group(function () {
            Route::get('collectors', [CollectorController::class, 'index'])->name('collectors.index');
            Route::get('collectors/create', [CollectorController::class, 'create'])->name('collectors.create');
            Route::post('collectors', [CollectorController::class, 'store'])->name('collectors.store');
            Route::get('collectors/logs', [CollectorController::class, 'logs'])->name('collectors.logs');
            Route::post('collectors/preview', [CollectorController::class, 'preview'])->name('collectors.preview');
            Route::get('collectors/{collector}/edit', [CollectorController::class, 'edit'])->name('collectors.edit');
            Route::put('collectors/{collector}', [CollectorController::class, 'update'])->name('collectors.update');
            Route::delete('collectors/{collector}', [CollectorController::class, 'destroy'])->name('collectors.destroy');
            Route::post('collectors/{collector}/run', [CollectorController::class, 'run'])->name('collectors.run');
            Route::post('collectors/{collector}/toggle', [CollectorController::class, 'toggle'])->name('collectors.toggle');
        });
        Route::middleware('admin.perm:settings')->group(function () {
            Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
            Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
            Route::get('themes', [ThemeController::class, 'index'])->name('themes.index');
            Route::post('themes', [ThemeController::class, 'store'])->name('themes.store');
            Route::post('themes/{theme}/use', [ThemeController::class, 'apply'])->name('themes.apply')->where('theme', '[a-z][a-z0-9_-]*');
            Route::delete('themes/{theme}', [ThemeController::class, 'destroy'])->name('themes.destroy')->where('theme', '[a-z][a-z0-9_-]*');
            Route::get('cache', [CacheController::class, 'edit'])->name('cache.edit');
            Route::post('cache/clear', [CacheController::class, 'clear'])->name('cache.clear');
            Route::post('cache/seed-demo', [CacheController::class, 'seedDemo'])->name('cache.seed-demo');
            Route::post('cache/backup', [CacheController::class, 'backup'])->name('cache.backup');
            Route::post('cache/backups/{file}/restore', [CacheController::class, 'restoreBackup'])->name('cache.backup.restore')
                ->where('file', 'cms-backup-\d{8}-\d{6}\.zip');
            Route::get('cache/backups/{file}', [CacheController::class, 'downloadBackup'])->name('cache.backup.download')
                ->where('file', 'cms-backup-\d{8}-\d{6}\.zip');
        });
        Route::middleware('admin.perm:html_cache')->group(function () {
            Route::get('html-cache', [HtmlCacheController::class, 'edit'])->name('html-cache.edit');
            Route::put('html-cache', [HtmlCacheController::class, 'update'])->name('html-cache.update');
            Route::post('html-cache/clear', [HtmlCacheController::class, 'clear'])->name('html-cache.clear');
            Route::post('html-cache/warm', [HtmlCacheController::class, 'warm'])->name('html-cache.warm');
            Route::put('html-cache/disk', [HtmlCacheController::class, 'updateDisk'])->name('html-cache.disk-update');
            Route::post('html-cache/disk-build', [HtmlCacheController::class, 'buildDisk'])->name('html-cache.disk-build');
            Route::post('html-cache/disk-step', [HtmlCacheController::class, 'stepDisk'])->name('html-cache.disk-step');
            Route::get('html-cache/disk-status', [HtmlCacheController::class, 'diskStatus'])->name('html-cache.disk-status');
            Route::post('html-cache/disk-cancel', [HtmlCacheController::class, 'cancelDisk'])->name('html-cache.disk-cancel');
            Route::post('html-cache/disk-clear', [HtmlCacheController::class, 'clearDisk'])->name('html-cache.disk-clear');
        });
        Route::middleware('admin.perm:logs')->group(function () {
            Route::get('logs', [AdminLogController::class, 'index'])->name('logs.index');
        });
        Route::middleware('admin.perm:admin_users')->group(function () {
            Route::post('admin-users/{admin_user}/toggle', [AdminUserController::class, 'toggle'])->name('admin-users.toggle');
            Route::resource('admin-users', AdminUserController::class)->except(['show']);
        });
        Route::middleware('admin.perm:roles')->group(function () {
            Route::resource('roles', RoleController::class)->except(['show']);
        });
    });
});
