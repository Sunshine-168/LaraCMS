<?php

use App\Http\Controllers\Api\CategoryApiController;
use App\Http\Controllers\Api\ContentApiController;
use App\Http\Controllers\Api\MemberApiController;
use App\Http\Controllers\Api\SiteApiController;
use App\Http\Controllers\Api\TagApiController;
use App\Http\Controllers\Api\WriteApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->middleware(['api', 'api.token', 'member.api'])->group(function () {
    Route::get('site/config', [SiteApiController::class, 'config']);
    Route::get('recommend', [SiteApiController::class, 'recommend']);

    Route::get('category/list', [CategoryApiController::class, 'listPage']);
    Route::get('category/tree', [CategoryApiController::class, 'tree']);
    Route::get('category/{id}', [CategoryApiController::class, 'detail'])->whereNumber('id');

    Route::get('content/list', [ContentApiController::class, 'listPage']);
    Route::get('content/{id}', [ContentApiController::class, 'details'])->whereNumber('id');
    Route::get('search', [ContentApiController::class, 'search']);

    Route::get('tag/list', [TagApiController::class, 'listPage']);
    Route::get('tag/{slug}', [TagApiController::class, 'detail']);

    Route::post('form/{slug}', [WriteApiController::class, 'form']);
    Route::post('comment/{entry}', [WriteApiController::class, 'comment'])->whereNumber('entry');

    Route::post('member/login', [MemberApiController::class, 'login']);
    Route::middleware('member.api:required')->group(function () {
        Route::post('member/logout', [MemberApiController::class, 'logout']);
        Route::get('member/me', [MemberApiController::class, 'me']);
        Route::put('member/profile', [MemberApiController::class, 'updateProfile']);
        Route::get('member/favorites', [MemberApiController::class, 'favorites']);
        Route::post('member/favorite/{entry}', [MemberApiController::class, 'toggleFavorite'])->whereNumber('entry');
    });
});
