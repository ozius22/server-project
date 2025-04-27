<?php

namespace App\Providers;

use App\Interfaces\Services\AuthServiceInterface;
use App\Interfaces\Services\LanguageServiceInterface;
use App\Services\AuthService;
use App\Services\LanguageService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(RepositoryServiceProvider::class);

        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(LanguageServiceInterface::class, LanguageService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
