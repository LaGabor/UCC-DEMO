<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password reset request</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #333; max-width: 600px; margin: 0 auto; padding: 1rem;">
    <h1 style="font-size: 1.25rem;">Password reset request</h1>
    <p>You have requested a password reset for your account at {{ config('app.name') }}.</p>
    <p>Click the link below to set a new password:</p>
    <p style="margin: 1.5rem 0;">
        <a href="{{ $resetUrl }}" style="display: inline-block; padding: 0.6rem 1.2rem; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 0.375rem;">Reset password</a>
    </p>
    <p style="font-size: 0.875rem; color: #666;">Or copy this link: {{ $resetUrl }}</p>
    <p style="font-size: 0.875rem; color: #666;">This link is valid for a limited time. If you did not request a reset, you can ignore this email.</p>
</body>
</html>
