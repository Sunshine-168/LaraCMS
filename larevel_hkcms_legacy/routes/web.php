<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\Cms\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\Cms\ContentController as AdminContentController;
use App\Http\Controllers\Admin\Cms\RecommendController as AdminRecommendController;
use App\Http\Controllers\Admin\Auth\AdminController as ManagedAdminController;
use App\Http\Controllers\Admin\Auth\GroupController as ManagedGroupController;
use App\Http\Controllers\Admin\Routine\ConfigController as AdminConfigController;
use App\Http\Controllers\Admin\Routine\AttachmentController as AdminAttachmentController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use App\Http\Controllers\Addons\AddressController;
use App\Http\Controllers\Common\UploadController;
use App\Http\Controllers\CmsFrontendController;
use Illuminate\Support\Facades\Route;

Route::get('/addons/address/index/index', [AddressController::class, 'index'])->name('addons.address.index');
Route::post('/common/upload', UploadController::class)
    ->withoutMiddleware([VerifyCsrfToken::class])
    ->name('common.upload');

Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::match(['get', 'post'], 'login', [AdminAuthController::class, 'login'])->name('login');

    Route::middleware('admin.auth')->group(function (): void {
        Route::post('logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('home');
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->middleware('admin.permission:index/dashboard')
            ->name('dashboard');
        Route::get('cms/categories', [AdminCategoryController::class, 'index'])
            ->middleware('admin.permission:cms/category/index')
            ->name('categories.index');
        Route::get('cms/categories/create', [AdminCategoryController::class, 'create'])
            ->middleware('admin.permission:cms/category/add')
            ->name('categories.create');
        Route::post('cms/categories', [AdminCategoryController::class, 'store'])
            ->middleware('admin.permission:cms/category/add')
            ->name('categories.store');
        Route::get('cms/categories/{category}/edit', [AdminCategoryController::class, 'edit'])
            ->middleware('admin.permission:cms/category/edit')
            ->name('categories.edit');
        Route::put('cms/categories/{category}', [AdminCategoryController::class, 'update'])
            ->middleware('admin.permission:cms/category/edit')
            ->name('categories.update');
        Route::delete('cms/categories/{category}', [AdminCategoryController::class, 'destroy'])
            ->middleware('admin.permission:cms/category/del')
            ->name('categories.destroy');
        Route::get('cms/content', [AdminContentController::class, 'index'])
            ->middleware('admin.permission:cms/content/index')
            ->name('contents.index');
        Route::get('cms/content/create', [AdminContentController::class, 'create'])
            ->middleware('admin.permission:cms/content/index')
            ->name('contents.create');
        Route::post('cms/content', [AdminContentController::class, 'store'])
            ->middleware('admin.permission:cms/content/index')
            ->name('contents.store');
        Route::get('cms/content/{content}/edit', [AdminContentController::class, 'edit'])
            ->middleware('admin.permission:cms/content/index')
            ->name('contents.edit');
        Route::put('cms/content/{content}', [AdminContentController::class, 'update'])
            ->middleware('admin.permission:cms/content/index')
            ->name('contents.update');
        Route::delete('cms/content/{content}', [AdminContentController::class, 'destroy'])
            ->middleware('admin.permission:cms/content/index')
            ->name('contents.destroy');
        Route::get('cms/recommend', [AdminRecommendController::class, 'index'])
            ->middleware('admin.permission:cms/recommend/index')
            ->name('recommends.index');
        Route::get('cms/recommend/create', [AdminRecommendController::class, 'create'])
            ->middleware('admin.permission:cms/recommend/add')
            ->name('recommends.create');
        Route::post('cms/recommend', [AdminRecommendController::class, 'store'])
            ->middleware('admin.permission:cms/recommend/add')
            ->name('recommends.store');
        Route::get('cms/recommend/{recommend}/edit', [AdminRecommendController::class, 'edit'])
            ->middleware('admin.permission:cms/recommend/edit')
            ->name('recommends.edit');
        Route::put('cms/recommend/{recommend}', [AdminRecommendController::class, 'update'])
            ->middleware('admin.permission:cms/recommend/edit')
            ->name('recommends.update');
        Route::delete('cms/recommend/{recommend}', [AdminRecommendController::class, 'destroy'])
            ->middleware('admin.permission:cms/recommend/del')
            ->name('recommends.destroy');
        Route::get('routine/attachment', [AdminAttachmentController::class, 'index'])
            ->middleware('admin.permission:routine/attachment/index')
            ->name('attachments.index');
        Route::get('routine/attachment/select', [AdminAttachmentController::class, 'select'])
            ->middleware('admin.permission:routine/attachment/index')
            ->name('attachments.select');
        Route::post('routine/attachment/download', [AdminAttachmentController::class, 'download'])
            ->middleware('admin.permission:routine/attachment/index')
            ->name('attachments.download');
        Route::post('routine/attachment/edit-title', [AdminAttachmentController::class, 'editTitle'])
            ->middleware('admin.permission:routine/attachment/edit')
            ->name('attachments.edit-title');
        Route::post('routine/attachment/get-title', [AdminAttachmentController::class, 'getTitle'])
            ->middleware('admin.permission:routine/attachment/index')
            ->name('attachments.get-title');
        Route::match(['get', 'post'], 'routine/attachment/water', [AdminAttachmentController::class, 'water'])
            ->middleware('admin.permission:routine/attachment/water')
            ->name('attachments.water');
        Route::match(['get', 'post'], 'routine/attachment/thumb', [AdminAttachmentController::class, 'thumb'])
            ->middleware('admin.permission:routine/attachment/thumb')
            ->name('attachments.thumb');
        Route::get('routine/attachment/{attachment}/edit', [AdminAttachmentController::class, 'edit'])
            ->middleware('admin.permission:routine/attachment/edit')
            ->name('attachments.edit');
        Route::put('routine/attachment/{attachment}', [AdminAttachmentController::class, 'update'])
            ->middleware('admin.permission:routine/attachment/edit')
            ->name('attachments.update');
        Route::delete('routine/attachment/{attachment}', [AdminAttachmentController::class, 'destroy'])
            ->middleware('admin.permission:routine/attachment/del')
            ->name('attachments.destroy');
        Route::match(['get', 'post'], 'routine/config', [AdminConfigController::class, 'index'])
            ->middleware('admin.permission:routine/config/index')
            ->name('config.index');
        Route::get('routine/config/create', [AdminConfigController::class, 'create'])
            ->middleware('admin.permission:routine/config/index')
            ->name('config.create');
        Route::post('routine/config/store', [AdminConfigController::class, 'store'])
            ->middleware('admin.permission:routine/config/index')
            ->name('config.store');
        Route::delete('routine/config/{config}', [AdminConfigController::class, 'destroy'])
            ->middleware('admin.permission:routine/config/index')
            ->name('config.destroy');
        Route::get('auth/admin', [ManagedAdminController::class, 'index'])
            ->middleware('admin.permission:auth/admin/index')
            ->name('admin-users.index');
        Route::get('auth/admin/create', [ManagedAdminController::class, 'create'])
            ->middleware('admin.permission:auth/admin/add')
            ->name('admin-users.create');
        Route::post('auth/admin', [ManagedAdminController::class, 'store'])
            ->middleware('admin.permission:auth/admin/add')
            ->name('admin-users.store');
        Route::get('auth/admin/{admin}/edit', [ManagedAdminController::class, 'edit'])
            ->middleware('admin.permission:auth/admin/edit')
            ->name('admin-users.edit');
        Route::put('auth/admin/{admin}', [ManagedAdminController::class, 'update'])
            ->middleware('admin.permission:auth/admin/edit')
            ->name('admin-users.update');
        Route::delete('auth/admin/{admin}', [ManagedAdminController::class, 'destroy'])
            ->middleware('admin.permission:auth/admin/del')
            ->name('admin-users.destroy');
        Route::get('auth/group', [ManagedGroupController::class, 'index'])
            ->middleware('admin.permission:auth/group/index')
            ->name('auth-groups.index');
        Route::get('auth/group/create', [ManagedGroupController::class, 'create'])
            ->middleware('admin.permission:auth/group/add')
            ->name('auth-groups.create');
        Route::post('auth/group', [ManagedGroupController::class, 'store'])
            ->middleware('admin.permission:auth/group/add')
            ->name('auth-groups.store');
        Route::get('auth/group/{group}/edit', [ManagedGroupController::class, 'edit'])
            ->middleware('admin.permission:auth/group/edit')
            ->name('auth-groups.edit');
        Route::put('auth/group/{group}', [ManagedGroupController::class, 'update'])
            ->middleware('admin.permission:auth/group/edit')
            ->name('auth-groups.update');
        Route::delete('auth/group/{group}', [ManagedGroupController::class, 'destroy'])
            ->middleware('admin.permission:auth/group/del')
            ->name('auth-groups.destroy');
    });
});

Route::get('/', [CmsFrontendController::class, 'index'])->name('cms.home');
Route::get('/{catname}/{id}', [CmsFrontendController::class, 'show'])
    ->where([
        'catname' => '[A-Za-z0-9_-]+',
        'id' => '[A-Za-z0-9_-]+',
    ])
    ->name('cms.content.show');
Route::get('/{catname}', [CmsFrontendController::class, 'list'])
    ->where('catname', '[A-Za-z0-9_-]+')
    ->name('cms.category.show');
