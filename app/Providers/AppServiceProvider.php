<?php

namespace App\Providers;

use App\Contracts\Repositories\PasswordResetRepositoryInterface;
use App\Contracts\Repositories\UserInvitationRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\PasswordResetServiceInterface;
use App\Contracts\Services\UserInvitationServiceInterface;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserInvitationRepository;
use App\Repositories\UserRepository;
use App\Services\PasswordResetService;
use App\Services\UserInvitationService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            UserInvitationServiceInterface::class,
            UserInvitationService::class
        );

        $this->app->bind(
            PasswordResetServiceInterface::class,
            PasswordResetService::class
        );

        $this->app->bind(
            UserRepositoryInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            UserInvitationRepositoryInterface::class,
            UserInvitationRepository::class
        );

        $this->app->bind(
            PasswordResetRepositoryInterface::class,
            PasswordResetRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
