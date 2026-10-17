<?php

namespace App\Providers;

use App\Ai\AiManager;
use App\Publishing\PublisherRegistry;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PublisherRegistry::class);
        $this->app->singleton(AiManager::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
    }
}
