<?php

namespace App\Providers;

use App\Contracts\Repositories\PasswordResetRepositoryInterface;
use App\Contracts\Repositories\EventRepositoryInterface;
use App\Contracts\Repositories\UserInvitationRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\PasswordResetServiceInterface;
use App\Contracts\Services\EventServiceInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Contracts\Services\UserInvitationServiceInterface;
use App\Repositories\EventRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserInvitationRepository;
use App\Repositories\UserRepository;
use App\Services\EventService;
use App\Services\PasswordResetService;
use App\Services\UserService;
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
            EventServiceInterface::class,
            EventService::class
        );

        $this->app->bind(
            UserServiceInterface::class,
            UserService::class
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

        $this->app->bind(
            EventRepositoryInterface::class,
            EventRepository::class
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
