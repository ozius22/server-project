<?php

namespace App\Providers;

use App\Interfaces\Repositories\CountryRepositoryInterface;
use App\Interfaces\Repositories\ImageRepositoryInterface;
use App\Interfaces\Repositories\LanguageRepositoryInterface;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Repositories\CountryRepository;
use App\Repositories\ImageRepository;
use App\Repositories\LanguageRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(LanguageRepositoryInterface::class, LanguageRepository::class);
        $this->app->bind(CountryRepositoryInterface::class, CountryRepository::class);
        $this->app->bind(ImageRepositoryInterface::class, ImageRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
