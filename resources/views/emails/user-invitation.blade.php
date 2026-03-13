<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>You have been invited</title>
</head>
<body style="font-family: sans-serif; line-height: 1.5; color: #333; max-width: 600px; margin: 0 auto; padding: 1rem;">
    <h1 style="font-size: 1.25rem;">You have been invited</h1>
    <p>You have been invited to join {{ config('app.name') }}.</p>
    <p>Click the link below to accept the invitation and complete your registration:</p>
    <p style="margin: 1.5rem 0;">
        <a href="{{ $invitationUrl }}" style="display: inline-block; padding: 0.6rem 1.2rem; background: #0d6efd; color: #fff; text-decoration: none; border-radius: 0.375rem;">Accept invitation</a>
    </p>
    <p style="font-size: 0.875rem; color: #666;">Or copy this link: {{ $invitationUrl }}</p>
    <p style="font-size: 0.875rem; color: #666;">This link is valid for a limited time.</p>
</body>
</html>
