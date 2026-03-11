<?php

use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\AttachApiRequestContext;
use App\Support\ApiResponse;
use App\Exceptions\ApiDomainException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->appendToGroup('api', AttachApiRequestContext::class);
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ApiDomainException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error(
                $e->errorCode->value,
                $e->getMessage(),
                $e->errors,
                $e->status->value
            );
        });

        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error(
                'VALIDATION_ERROR',
                'Validation failed.',
                $e->errors(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        });

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error(
                'UNAUTHENTICATED',
                'Authentication is required.',
                null,
                Response::HTTP_UNAUTHORIZED
            );
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error(
                'FORBIDDEN',
                'You are not allowed to perform this action.',
                null,
                Response::HTTP_FORBIDDEN
            );
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            return ApiResponse::error(
                'NOT_FOUND',
                'The requested resource was not found.',
                null,
                Response::HTTP_NOT_FOUND
            );
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $status = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return ApiResponse::error(
                'INTERNAL_SERVER_ERROR',
                app()->hasDebugModeEnabled() ? $e->getMessage() : 'An unexpected error occurred.',
                null,
                $status
            );
        });
    })->create();
