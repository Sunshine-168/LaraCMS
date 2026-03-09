<?php

use App\Http\Controllers\Api\Cms\CategoryController;
use App\Http\Controllers\Api\Cms\ContentController;
use App\Http\Controllers\Api\Cms\RecommendController;
use App\Http\Controllers\Api\Cms\SiteConfigController;
use Illuminate\Support\Facades\Route;

Route::prefix('category')->group(function (): void {
    Route::match(['get', 'post'], 'listPage', [CategoryController::class, 'listPage']);
    Route::get('detail', [CategoryController::class, 'detail']);
    Route::get('tree', [CategoryController::class, 'tree']);
});

Route::prefix('content')->group(function (): void {
    Route::match(['get', 'post'], 'listPage', [ContentController::class, 'listPage']);
    Route::get('details', [ContentController::class, 'details']);
});

Route::get('siteConfig/getAll', [SiteConfigController::class, 'getAll']);
Route::get('recommend/contentList', [RecommendController::class, 'contentList']);
