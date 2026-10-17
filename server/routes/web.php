<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DestinationController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TitleListController;
use App\Http\Controllers\WalletController;
use Illuminate\Support\Facades\Route;

// Public home page is only an "under construction" page on purpose.
Route::view('/', 'home');

// The login page is on an uncommon address to keep robots away. No sign-up: admins create customers.
Route::get('/mainadmin', [AuthController::class, 'showLogin'])->name('login');
Route::post('/mainadmin', [AuthController::class, 'login'])->middleware('throttle:10,1');
Route::get('/locale/{locale}', [AuthController::class, 'locale'])->name('locale');

// Article images. Public on purpose: Instagram/Facebook/webhooks must fetch them. The UUID is unguessable.
Route::get('/media/{uuid}.{ext}', [MediaController::class, 'show'])->whereUuid('uuid')->whereIn('ext', ['jpg'])->name('media.show');

Route::middleware('auth')->prefix('mainadmin')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('services', ServiceController::class);
    Route::post('/services/{service}/run', [ServiceController::class, 'run'])->name('services.run');
    Route::post('/services/{service}/token', [ServiceController::class, 'rotateToken'])->name('services.token');
    Route::post('/services/{service}/secret', [ServiceController::class, 'rotateSecret'])->name('services.secret');
    Route::post('/services/{service}/duplicate', [ServiceController::class, 'duplicate'])->name('services.duplicate');
    Route::post('/services/{service}/toggle', [ServiceController::class, 'toggle'])->name('services.toggle');

    Route::post('/title-lists/quick', [TitleListController::class, 'quickStore'])->name('title-lists.quick');
    Route::resource('title-lists', TitleListController::class)->except('show');

    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/{article}', [ArticleController::class, 'show'])->name('articles.show');
    Route::delete('/articles/{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    Route::post('/articles/{article}/retry', [ArticleController::class, 'retry'])->name('articles.retry');
    Route::post('/deliveries/{delivery}/resend', [ArticleController::class, 'resend'])->name('deliveries.resend');

    Route::resource('destinations', DestinationController::class)->except('show');

    Route::get('/wallet', WalletController::class)->name('wallet');
    Route::get('/docs', DocsController::class)->name('docs');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('customers', Admin\CustomerController::class)->except('show')->parameters(['customers' => 'user']);
        Route::post('/customers/{user}/wallet', [Admin\CustomerController::class, 'wallet'])->name('customers.wallet');
        Route::resource('providers', Admin\AiProviderController::class)->except('show')->parameters(['providers' => 'provider']);
        Route::resource('agents', Admin\AiAgentController::class)->except('show')->parameters(['agents' => 'agent']);
        Route::resource('image-sizes', Admin\ImageSizeController::class)->except('show')->parameters(['image-sizes' => 'size']);
        Route::resource('tones', Admin\ToneController::class)->except('show');
        Route::get('/settings', [Admin\SettingsController::class, 'edit'])->name('settings');
        Route::put('/settings', [Admin\SettingsController::class, 'update'])->name('settings.update');
    });
});
