<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdminOrHelpdeskAgent
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.',
            ], 401);
        }

        if ($user->status !== UserStatus::ACTIVE) {
            return response()->json([
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Active account required.',
            ], 403);
        }

        if (! in_array($user->role, [UserRole::ADMIN, UserRole::HELPDESK_AGENT], true)) {
            return response()->json([
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Admin or helpdesk agent access required.',
            ], 403);
        }

        return $next($request);
    }
}
