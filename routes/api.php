<?php

use App\Http\Controllers\Api\Admin\UserInvitationController;
use App\Http\Controllers\Api\AgentMonitorController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\UserCommunicationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\Public\UserInvitationController as PublicUserInvitationController;
use App\Http\Controllers\Api\Public\PasswordResetController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')
    ->put('/user/preferred-locale', [UserController::class, 'updatePreferredLocale'])
    ->name('api.user.preferred-locale.update');

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {
        Route::post('/user-invitations', [UserInvitationController::class, 'store']);
    });

Route::middleware(['auth:sanctum', 'agent_monitor'])
    ->prefix('agent-monitor')
    ->group(function () {
        Route::get('/conversations', [AgentMonitorController::class, 'index'])->name('api.agent-monitor.conversations.index');
    });

Route::middleware('auth:sanctum')
    ->prefix('events')
    ->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('api.events.index');
        Route::post('/', [EventController::class, 'store'])->name('api.events.store');
        Route::get('/{event}', [EventController::class, 'show'])->name('api.events.show');
        Route::put('/{event}', [EventController::class, 'update'])->name('api.events.update');
        Route::delete('/{event}', [EventController::class, 'destroy'])->name('api.events.destroy');
    });

Route::middleware('auth:sanctum')
    ->prefix('communication')
    ->group(function () {
        Route::get('/user-conversation', [UserCommunicationController::class, 'getUserConversation'])
            ->name('api.communication.user-conversation.get');
        Route::post('/user-message', [UserCommunicationController::class, 'sendUserMessage'])
            ->middleware('throttle:user-message-per-second')
            ->name('api.communication.user-message.post');
        Route::patch('/call-agent', [UserCommunicationController::class, 'callAgent'])
            ->name('api.communication.call-agent.patch');
        Route::patch('/cancel-call', [UserCommunicationController::class, 'cancelCall'])
            ->name('api.communication.cancel-call.patch');
    });

Route::prefix('public')->group(function () {
    Route::get('/user-invitations/{token}', [PublicUserInvitationController::class, 'show'])
        ->name('api.public.user-invitations.show');

    Route::post('/user-invitations/{token}/accept', [PublicUserInvitationController::class, 'accept'])
        ->name('api.public.user-invitations.accept');

    Route::post('/password-reset-requests', [PasswordResetController::class, 'store'])
        ->name('api.public.password-reset-requests.store');

    Route::get('/password-reset-requests/{token}', [PasswordResetController::class, 'show'])
        ->name('api.public.password-reset-requests.show');

    Route::post('/password-reset-requests/{token}/complete', [PasswordResetController::class, 'update'])
        ->name('api.public.password-reset-requests.complete');
});
