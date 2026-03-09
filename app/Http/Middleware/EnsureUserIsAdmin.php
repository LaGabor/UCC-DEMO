<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'code' => 'UNAUTHENTICATED',
                'message' => 'Authentication required.'
            ], 401);
        }

        if ($user->role !== 'admin' || $user->status !== 'active') {
            return response()->json([
                'success' => false,
                'code' => 'FORBIDDEN',
                'message' => 'Admin access required.'
            ], 403);
        }

        return $next($request);
    }
}
