<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable
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
            subject: 'You have been invited',
        );
    }

    public function content(): Content
    {
        $baseUrl = rtrim(config('app.url'), '/');

        return new Content(
            view: 'emails.user-invitation',
            with: [
                'invitationUrl' => $baseUrl.'/invitations/accept/'.$this->token,
            ],
        );
    }
}
