<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Log;

class LogFailedLoginAttempt
{
    
    public function handle(Failed $event): void
    {
        $identifier = collect($event->credentials)
            ->except(['password', 'password_confirmation'])
            ->filter()
            ->all();

        Log::warning('security.auth.login_failed', [
            'guard' => $event->guard,
            'identifier' => $identifier,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
