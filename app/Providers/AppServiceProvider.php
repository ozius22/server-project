<?php

namespace App\Providers;

use App\Interfaces\Services\AuthServiceInterface;
use App\Interfaces\Services\CountryServiceInterface;
use App\Interfaces\Services\ImageServiceInterface;
use App\Interfaces\Services\LanguageServiceInterface;
use App\Services\AuthService;
use App\Services\CountryService;
use App\Services\ImageService;
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
        $this->app->bind(CountryServiceInterface::class, CountryService::class);
        $this->app->bind(ImageServiceInterface::class, ImageService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
