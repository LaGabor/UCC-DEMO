<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $token
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password reset request',
        );
    }

    public function content(): Content
    {
        $baseUrl = rtrim(config('app.url'), '/');

        return new Content(
            view: 'emails.password-reset',
            with: [
                'resetUrl' => $baseUrl.'/password-reset/'.$this->token,
            ],
        );
    }
}
