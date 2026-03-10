<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;
use PragmaRX\Google2FAQRCode\Google2FA;

Route::get('/auth/check', function (Request $request) {
    return response()->json([
        'authenticated' => (bool) $request->user(),
        'user' => $request->user(),
    ]);
});

Route::middleware('auth')->group(function () {
    Route::post('/user/two-factor-authentication', function (Request $request, EnableTwoFactorAuthentication $enable) {
        $enable($request->user());

        return response()->json([
            'message' => 'Two-factor authentication enabled.',
        ]);
    });

    Route::delete('/user/two-factor-authentication', function (Request $request, DisableTwoFactorAuthentication $disable) {
        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->string('current_password')->toString(), $request->user()->password)) {
            return response()->json([
                'message' => 'The provided password is incorrect.',
                'errors' => [
                    'current_password' => ['The provided password is incorrect.'],
                ],
            ], 422);
        }

        $disable($request->user());

        return response()->json([
            'message' => 'Two-factor authentication disabled.',
        ]);
    });

    Route::post('/user/two-factor-recovery-codes', function (Request $request, GenerateNewRecoveryCodes $generate) {
        $request->validate([
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($request->string('current_password')->toString(), $request->user()->password)) {
            return response()->json([
                'message' => 'The provided password is incorrect.',
                'errors' => [
                    'current_password' => ['The provided password is incorrect.'],
                ],
            ], 422);
        }

        $generate($request->user());

        return response()->json([
            'message' => 'Recovery codes regenerated.',
        ]);
    });

    Route::get('/user/two-factor-qr-code', function (Request $request) {
        $user = $request->user();

        abort_unless($user->two_factor_secret, 404);

        $google2fa = app(Google2FA::class);

        $svg = $google2fa->getQRCodeInline(
            config('app.name'),
            $user->email,
            decrypt($user->two_factor_secret)
        );

        return response()->json([
            'svg' => $svg,
        ]);
    });

    Route::get('/user/two-factor-recovery-codes', function (Request $request) {
        $user = $request->user();

        abort_unless($user->two_factor_recovery_codes, 404);

        return response()->json([
            'recovery_codes' => decrypt($user->two_factor_recovery_codes),
        ]);
    });

    Route::post('/user/confirmed-two-factor-authentication', function (Request $request) {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();

        if (! $user->two_factor_secret) {
            return response()->json([
                'message' => 'Two-factor authentication is not enabled.',
            ], 422);
        }

        $google2fa = app(Google2FA::class);

        $isValid = $google2fa->verifyKey(
            decrypt($user->two_factor_secret),
            $request->string('code')->toString()
        );

        if (! $isValid) {
            return response()->json([
                'message' => 'Invalid authentication code.',
            ], 422);
        }

        $user->forceFill([
            'two_factor_confirmed_at' => now(),
        ])->save();

        return response()->json([
            'message' => 'Two-factor authentication confirmed.',
        ]);
    });
});

Route::view('/{any?}', 'app')
    ->where('any', '.*');
