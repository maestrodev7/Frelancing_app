<?php

namespace App\Providers;

use App\Application\Auth\Ports\ClientProfileRepository;
use App\Application\Auth\Ports\PasswordHasher;
use App\Application\Auth\Ports\UserRepository;
use App\Application\Shared\Ports\TransactionManager;
use App\Infrastructure\Auth\LaravelPasswordHasher;
use App\Infrastructure\Persistence\DatabaseTransactionManager;
use App\Infrastructure\Persistence\EloquentClientProfileRepository;
use App\Infrastructure\Persistence\EloquentUserRepository;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(ClientProfileRepository::class, EloquentClientProfileRepository::class);
        $this->app->bind(PasswordHasher::class, LaravelPasswordHasher::class);
        $this->app->bind(TransactionManager::class, DatabaseTransactionManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
