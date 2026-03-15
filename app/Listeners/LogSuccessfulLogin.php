<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class LogSuccessfulLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        Log::info('security.auth.login_success', [
            'user_id' => $user->getAuthIdentifier(),
            'guard' => $event->guard,
            'remember' => $event->remember,
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }
}
