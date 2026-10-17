<?php

use App\Http\Controllers\Api\ArticleApiController;
use Illuminate\Support\Facades\Route;

// Pull API for target apps, with the service token (Authorization: Bearer <token>).
Route::get('/openapi.json', [ArticleApiController::class, 'openapi'])->name('api.openapi');

Route::prefix('v1')->middleware('throttle:120,1')->group(function () {
    Route::get('/service', [ArticleApiController::class, 'service'])->name('api.service');
    Route::get('/articles', [ArticleApiController::class, 'index'])->name('api.articles');
    Route::get('/articles/{uuid}', [ArticleApiController::class, 'show'])->whereUuid('uuid')->name('api.article');
});
