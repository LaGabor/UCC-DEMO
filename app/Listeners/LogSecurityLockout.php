<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Log;

class LogSecurityLockout
{
    public function handle(Lockout $event): void
    {
        $request = $event->request;

        Log::warning('security.auth.lockout', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'identifier' => $request->input('email'),
        ]);
    }
}
