<?php

use App\Http\Controllers\Api\Admin\UserInvitationController;
use App\Http\Controllers\Api\Public\PasswordResetRequestController;
use App\Http\Controllers\Api\Public\UserInvitationAcceptanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'admin'])
    ->group(function () {
        Route::post('/user-invitations', [UserInvitationController::class, 'store']);
    });

Route::prefix('public')->group(function () {
    Route::get('/user-invitations/{token}', [UserInvitationAcceptanceController::class, 'show'])
        ->name('api.public.user-invitations.show');

    Route::post('/user-invitations/{token}/accept', [UserInvitationAcceptanceController::class, 'store'])
        ->name('api.public.user-invitations.accept');

    Route::post('/password-reset-requests', [PasswordResetRequestController::class, 'store'])
        ->name('api.public.password-reset-requests.store');

    Route::get('/password-reset-requests/{token}', [PasswordResetRequestController::class, 'show'])
        ->name('api.public.password-reset-requests.show');

    Route::post('/password-reset-requests/{token}/complete', [PasswordResetRequestController::class, 'complete'])
        ->name('api.public.password-reset-requests.complete');
});
