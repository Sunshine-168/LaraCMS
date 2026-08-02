<?php

use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\ContentApiController;
use App\Http\Controllers\Api\SiteApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware(['api', 'api.token'])->group(function () {
    Route::get('site/config', [SiteApiController::class, 'config']);
    Route::get('recommend', [SiteApiController::class, 'recommend']);

    Route::get('category/list', [CategoryApiController::class, 'listPage']);
    Route::get('category/tree', [CategoryApiController::class, 'tree']);
    Route::get('category/{id}', [CategoryApiController::class, 'detail'])->whereNumber('id');

    Route::get('content/list', [ContentApiController::class, 'listPage']);
    Route::get('content/{id}', [ContentApiController::class, 'details'])->whereNumber('id');
});
