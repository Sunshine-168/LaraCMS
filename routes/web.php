<?php

use App\Http\Controllers\Web\CommentController;
use App\Http\Controllers\Web\MemberController;
use App\Http\Controllers\Web\MemberEntryController;
use App\Http\Controllers\Web\SiteController;
use App\Support\Locale;
use Illuminate\Support\Facades\Route;

$reserved = array_map(
    fn (string $code) => preg_quote(Locale::toPublic($code), '/'),
    array_values(array_filter(
        Locale::codes(),
        fn (string $code) => ! (Locale::hideDefault() && $code === Locale::default())
    ))
);

$reservedSegments = array_merge(
    ['admin', 'storage', 'livewire', 'api', 'download', 'go', 'form', 'comment', 'search', 'member', 'login', 'logout', 'register', 'tag', 'rss', 'rss\\.xml', 'robots\\.txt', 'install', 'e', 'c', 'preview', 'sitemap\\.xml'],
    $reserved
);
$slugExclude = '^(?!'.implode('$|', $reservedSegments).'$)[A-Za-z0-9\-_]+$';
$catExclude = $slugExclude;

$registerContent = function (string $nameSuffix = '') use ($slugExclude, $catExclude) {
    Route::get('/', [SiteController::class, 'index'])->name('home'.$nameSuffix);
    Route::get('/search', [SiteController::class, 'search'])->name('search'.$nameSuffix);
    Route::get('/tag/{slug}', [SiteController::class, 'tag'])->name('tag'.$nameSuffix);
    Route::get('/sitemap.xml', [SiteController::class, 'sitemap'])->name('sitemap'.$nameSuffix);
    Route::get('/robots.txt', [SiteController::class, 'robots'])->name('robots'.$nameSuffix);
    Route::get('/rss.xml', [SiteController::class, 'rss'])->name('rss'.$nameSuffix);
    Route::get('/preview/{token}', [SiteController::class, 'preview'])->name('entry.preview'.$nameSuffix)->where('token', '[A-Za-z0-9]{16,64}');
    Route::get('/e/{entry}', [SiteController::class, 'showById'])->name('entry.id'.$nameSuffix)->whereNumber('entry');
    Route::get('/c/{category}', [SiteController::class, 'categoryById'])->name('category.id'.$nameSuffix)->whereNumber('category');
    Route::get('/c/{category}/{slug}', [SiteController::class, 'showByCatId'])->name('entry.catid'.$nameSuffix)
        ->whereNumber('category')->where('slug', '[A-Za-z0-9\-_]+');
    Route::get('/member/login', [MemberController::class, 'showLogin'])->name('member.login'.$nameSuffix);
    Route::post('/member/login', [MemberController::class, 'login'])->middleware('throttle:5,1')->name('member.login.submit'.$nameSuffix);
    Route::get('/member/register', [MemberController::class, 'showRegister'])->name('member.register'.$nameSuffix);
    Route::post('/member/register', [MemberController::class, 'register'])->middleware('throttle:5,1')->name('member.register.submit'.$nameSuffix);
    Route::get('/member/forgot', [MemberController::class, 'showForgot'])->name('member.forgot'.$nameSuffix);
    Route::post('/member/forgot', [MemberController::class, 'sendReset'])->middleware('throttle:5,1')->name('member.forgot.submit'.$nameSuffix);
    Route::get('/member/reset', [MemberController::class, 'showReset'])->name('member.reset'.$nameSuffix);
    Route::post('/member/reset', [MemberController::class, 'resetPassword'])->middleware('throttle:5,1')->name('member.reset.submit'.$nameSuffix);
    Route::get('/member/profile', [MemberController::class, 'profile'])->name('member.profile'.$nameSuffix);
    Route::post('/member/profile', [MemberController::class, 'updateProfile'])->name('member.profile.update'.$nameSuffix);
    Route::get('/member/entries', [MemberEntryController::class, 'index'])->name('member.entries.index'.$nameSuffix);
    Route::get('/member/entries/create', [MemberEntryController::class, 'create'])->name('member.entries.create'.$nameSuffix);
    Route::post('/member/entries', [MemberEntryController::class, 'store'])->name('member.entries.store'.$nameSuffix);
    Route::get('/member/entries/{entry}/edit', [MemberEntryController::class, 'edit'])->name('member.entries.edit'.$nameSuffix)->whereNumber('entry');
    Route::put('/member/entries/{entry}', [MemberEntryController::class, 'update'])->name('member.entries.update'.$nameSuffix)->whereNumber('entry');
    Route::post('/member/favorite/{entry}', [MemberController::class, 'toggleFavorite'])->name('member.favorite'.$nameSuffix)->whereNumber('entry');
    Route::post('/member/password', [MemberController::class, 'updatePassword'])->name('member.password'.$nameSuffix);
    Route::post('/member/upload/init', [MemberController::class, 'uploadInit'])->name('member.upload.init'.$nameSuffix);
    Route::post('/member/upload/chunk', [MemberController::class, 'uploadChunk'])->name('member.upload.chunk'.$nameSuffix);
    Route::post('/member/upload/complete', [MemberController::class, 'uploadComplete'])->name('member.upload.complete'.$nameSuffix);
    Route::post('/member/logout', [MemberController::class, 'logout'])->name('member.logout'.$nameSuffix);
    Route::get('/{modelSlug}/{catSlug}/{slug}', [SiteController::class, 'showByModel'])
        ->where(['modelSlug' => $slugExclude, 'catSlug' => '[A-Za-z0-9\-_]+', 'slug' => '[A-Za-z0-9\-_]+'])
        ->name('entry.model'.$nameSuffix);
    Route::get('/download/{entry}', [SiteController::class, 'download'])->name('download'.$nameSuffix);
    Route::get('/go/{entry}', [SiteController::class, 'go'])->name('go'.$nameSuffix);
    Route::post('/form/{slug}', [SiteController::class, 'submitForm'])->middleware('throttle:10,1')->name('form.submit'.$nameSuffix);
    Route::post('/comment/{entry}', [CommentController::class, 'store'])->middleware('throttle:10,1')->name('comment.store'.$nameSuffix)->whereNumber('entry');
    Route::get('/{catSlug}/{slug}', [SiteController::class, 'show'])
        ->where(['catSlug' => $catExclude, 'slug' => '[A-Za-z0-9\-_]+'])
        ->name('entry.show'.$nameSuffix);
    Route::get('/{slug}', [SiteController::class, 'category'])
        ->where('slug', $slugExclude)
        ->name('category.show'.$nameSuffix);
};

foreach (Locale::codes() as $code) {
    if ($code === Locale::default() && Locale::hideDefault()) {
        continue;
    }
    $public = Locale::toPublic($code);
    Route::prefix($public)
        ->middleware(['locale:'.$code, 'html.cache'])
        ->group(function () use ($registerContent, $code) {
            $registerContent('.'.$code);
        });
}

Route::middleware(['locale:'.Locale::default(), 'html.cache'])->group(function () use ($registerContent) {
    $registerContent();
});
