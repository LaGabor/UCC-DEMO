<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AttachApiRequestContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->resolveRequestId($request);
        $request->attributes->set('request_id', $requestId);

        Log::withContext([
            'request_id' => $requestId,
            'http_method' => $request->method(),
            'path' => $request->path(),
            'route' => optional($request->route())->getName(),
            'user_id' => optional($request->user())->id,
        ]);

        /** @var Response $response */
        $response = $next($request);
        $response->headers->set('X-Request-Id', $requestId);

        if ($response->getStatusCode() >= 500) {
            Log::error('api.response.server_error', [
                'request_id' => $requestId,
                'status' => $response->getStatusCode(),
                'route' => optional($request->route())->getName(),
            ]);
        }

        return $response;
    }

    private function resolveRequestId(Request $request): string
    {
        $incoming = (string) $request->headers->get('X-Request-Id', '');
        if ($incoming !== '') {
            return Str::limit($incoming, 100, '');
        }

        return (string) Str::uuid();
    }
}

