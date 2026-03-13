<?php

namespace App\Providers;

use App\Contracts\Repositories\AgentMonitorRepositoryInterface;
use App\Contracts\Repositories\PasswordResetRepositoryInterface;
use App\Contracts\Repositories\EventRepositoryInterface;
use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Repositories\UserInvitationRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AgentMonitorServiceInterface;
use App\Contracts\Services\PasswordResetServiceInterface;
use App\Contracts\Services\EventServiceInterface;
use App\Contracts\Services\LargeLanguageMessageResponderServiceInterface;
use App\Contracts\Services\MessageRouterServiceInterface;
use App\Contracts\Services\SystemMessageBotServiceInterface;
use App\Contracts\Services\UserCommunicationServiceInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Contracts\Services\UserInvitationServiceInterface;
use App\Repositories\AgentMonitorRepository;
use App\Repositories\EventRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserCommunicationRepository;
use App\Repositories\UserInvitationRepository;
use App\Repositories\UserRepository;
use App\Services\AgentMonitorService;
use App\Services\EventService;
use App\Services\MessageRouterService;
use App\Services\OllamaResponderService;
use App\Services\PasswordResetService;
use App\Services\SystemMessageBotService;
use App\Services\UserCommunicationService;
use App\Services\UserService;
use App\Services\UserInvitationService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
            AgentMonitorRepositoryInterface::class,
            AgentMonitorRepository::class
        );

        $this->app->bind(
            UserCommunicationRepositoryInterface::class,
            UserCommunicationRepository::class
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

        $this->app->bind(
            AgentMonitorServiceInterface::class,
            AgentMonitorService::class
        );

        $this->app->bind(
            UserCommunicationServiceInterface::class,
            UserCommunicationService::class
        );

        $this->app->bind(
            MessageRouterServiceInterface::class,
            MessageRouterService::class
        );

        $this->app->bind(
            SystemMessageBotServiceInterface::class,
            SystemMessageBotService::class
        );

        $this->app->bind(
            LargeLanguageMessageResponderServiceInterface::class,
            OllamaResponderService::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('user-message-per-second', function (Request $request) {
            return Limit::perSecond(1)->by((string) $request->user()?->id);
        });
    }
}
