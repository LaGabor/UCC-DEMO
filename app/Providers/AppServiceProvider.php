<?php

namespace App\Providers;

use App\Contracts\Repositories\AgentMonitorCommunicationRepositoryInterface;
use App\Contracts\Repositories\AgentMonitorRepositoryInterface;
use App\Contracts\Repositories\ConversationHistoryRepositoryInterface;
use App\Contracts\Repositories\PasswordResetRepositoryInterface;
use App\Contracts\Repositories\EventRepositoryInterface;
use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Contracts\Repositories\UserInvitationRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Services\AgentMonitorCommunicationServiceInterface;
use App\Contracts\Services\AgentMonitorServiceInterface;
use App\Contracts\Services\ConversationHistoryServiceInterface;
use App\Contracts\Services\PasswordResetServiceInterface;
use App\Contracts\Services\EventServiceInterface;
use App\Contracts\Services\LargeLanguageMessageResponderServiceInterface;
use App\Contracts\Services\LLMServiceInterface;
use App\Contracts\Services\SystemMessageBotServiceInterface;
use App\Contracts\Services\UserCommunicationServiceInterface;
use App\Contracts\Services\UserServiceInterface;
use App\Contracts\Services\UserInvitationServiceInterface;
use App\Repositories\AgentMonitorCommunicationRepository;
use App\Repositories\AgentMonitorRepository;
use App\Repositories\ConversationHistoryRepository;
use App\Repositories\EventRepository;
use App\Repositories\PasswordResetRepository;
use App\Repositories\UserCommunicationRepository;
use App\Repositories\UserInvitationRepository;
use App\Repositories\UserRepository;
use App\Services\AgentMonitorCommunicationService;
use App\Services\AgentMonitorService;
use App\Services\ConversationHistoryService;
use App\Services\EventService;
use App\Services\LLMService;
use App\Services\OllamaResponderService;
use App\Services\PasswordResetService;
use App\Services\SystemMessageBotService;
use App\Services\UserCommunicationService;
use App\Services\UserService;
use App\Services\UserInvitationService;
use App\Models\Conversation;
use App\Models\ConversationMessage;
use App\Listeners\LogFailedLoginAttempt;
use App\Listeners\LogSecurityLockout;
use App\Listeners\LogSuccessfulLogin;
use App\Observers\ConversationMessageObserver;
use App\Observers\ConversationObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
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
            AgentMonitorCommunicationRepositoryInterface::class,
            AgentMonitorCommunicationRepository::class
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
            AgentMonitorCommunicationServiceInterface::class,
            AgentMonitorCommunicationService::class
        );

        $this->app->bind(
            ConversationHistoryRepositoryInterface::class,
            ConversationHistoryRepository::class
        );

        $this->app->bind(
            ConversationHistoryServiceInterface::class,
            ConversationHistoryService::class
        );

        $this->app->bind(
            UserCommunicationServiceInterface::class,
            UserCommunicationService::class
        );

        $this->app->bind(
            LLMServiceInterface::class,
            LLMService::class
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
        Conversation::observe(ConversationObserver::class);
        ConversationMessage::observe(ConversationMessageObserver::class);

        RateLimiter::for('per-second', function (Request $request) {
            $key = (string) ($request->user()?->id ?? $request->ip());
            $endpoint = $request->route()?->getName() ?? $request->path();

            return Limit::perSecond(3)->by($key.'|'.$endpoint);
        });

        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perMinutes(15, 5)->by((string) $request->ip());
        });

        Event::listen(Failed::class, LogFailedLoginAttempt::class);
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Lockout::class, LogSecurityLockout::class);
    }
}
