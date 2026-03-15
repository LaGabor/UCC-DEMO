<?php

use App\Http\Controllers\Api\Admin\UserInvitationController;
use App\Http\Controllers\Api\Agent\AgentMonitorCommunicationController;
use App\Http\Controllers\Api\Agent\AgentMonitorController;
use App\Http\Controllers\Api\Agent\ConversationHistoryController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\Public\PasswordResetController;
use App\Http\Controllers\Api\Public\UserInvitationController as PublicUserInvitationController;
use App\Http\Controllers\Api\UserCommunicationController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')
    ->patch('/user/preferred-locale', [UserController::class, 'updatePreferredLocale'])
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
        Route::get('/conversations/{conversationId}/history', [AgentMonitorCommunicationController::class, 'viewUserChatHistory'])->name('api.agent-monitor.conversations.history');
        Route::patch('/conversations/{conversationId}/answer', [AgentMonitorCommunicationController::class, 'answerUserChatHistory'])->name('api.agent-monitor.conversations.answer');
        Route::patch('/conversations/{conversationId}/close-assigned', [AgentMonitorCommunicationController::class, 'closeAssigned'])->name('api.agent-monitor.conversations.close-assigned');
        Route::patch('/conversations/{conversationId}/close-waiting-human', [AgentMonitorCommunicationController::class, 'closeWaitingHuman'])->name('api.agent-monitor.conversations.close-waiting-human');
        Route::patch('/close-agent-communication-status', [AgentMonitorCommunicationController::class, 'closeAgentCommunicationStatus'])->name('api.agent-monitor.close-agent-communication-status.patch');
        Route::post('/conversations/{conversationId}/messages', [AgentMonitorCommunicationController::class, 'sendAgentMessage'])->name('api.agent-monitor.conversations.messages.store');
    });

Route::middleware('auth:sanctum')
    ->prefix('conversation-history')
    ->group(function () {
        Route::get('/', [ConversationHistoryController::class, 'index'])->name('api.conversation-history.index');
        Route::get('/{conversationId}/messages', [ConversationHistoryController::class, 'show'])->name('api.conversation-history.messages');
    });

Route::middleware('auth:sanctum')
    ->prefix('events')
    ->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('api.events.index');
        Route::post('/', [EventController::class, 'store'])->name('api.events.store');
        Route::get('/{event}', [EventController::class, 'show'])->name('api.events.show');
        Route::patch('/{event}', [EventController::class, 'update'])->name('api.events.update');
        Route::delete('/{event}', [EventController::class, 'destroy'])->name('api.events.destroy');
    });

Route::middleware('auth:sanctum')
    ->prefix('communication')
    ->group(function () {
        Route::get('/user-conversation', [UserCommunicationController::class, 'getUserConversation'])
            ->name('api.communication.user-conversation.get');
        Route::post('/user-message', [UserCommunicationController::class, 'sendUserMessage'])
            ->name('api.communication.user-message.post');
        Route::patch('/call-agent', [UserCommunicationController::class, 'callAgent'])
            ->name('api.communication.call-agent.patch');
        Route::patch('/cancel-call', [UserCommunicationController::class, 'cancelCall'])
            ->name('api.communication.cancel-call.patch');
        Route::patch('/close-user-communication', [UserCommunicationController::class, 'closeUserCommunication'])
            ->name('api.communication.close-user-communication.patch');
    });

Route::prefix('public')->group(function () {
    Route::get('/user-invitations/{token}', [PublicUserInvitationController::class, 'show'])
        ->name('api.public.user-invitations.show');

    Route::patch('/user-invitations/{token}/accept', [PublicUserInvitationController::class, 'accept'])
        ->name('api.public.user-invitations.accept');

    Route::middleware('throttle:password-reset')->group(function () {
        Route::post('/password-reset-requests', [PasswordResetController::class, 'store'])
            ->name('api.public.password-reset-requests.store');

        Route::get('/password-reset-requests/{token}', [PasswordResetController::class, 'show'])
            ->name('api.public.password-reset-requests.show');

        Route::patch('/password-reset-requests/{token}/complete', [PasswordResetController::class, 'update'])
            ->name('api.public.password-reset-requests.complete');
    });
});
