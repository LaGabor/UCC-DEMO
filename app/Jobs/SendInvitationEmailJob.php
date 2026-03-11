<?php

namespace App\Jobs;

use App\Contracts\Repositories\UserInvitationRepositoryInterface;
use App\Mail\UserInvitationMail;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendInvitationEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    private const VALID_FOR_DAYS = 5;

    public function __construct(
        public readonly string $email,
        public readonly string $token
    ) {
        $this->onQueue('mail');
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function retryUntil(): \DateTimeInterface
    {
        return now()->addDay();
    }

    public function handle(UserInvitationRepositoryInterface $userInvitationRepository): void
    {
        $record = $userInvitationRepository->findInvitationTokenByToken($this->token);

        if (! $record || ! $record->created_at) {
            Log::warning('job.invitation_email.skipped_missing_or_invalid_token', [
                'job' => self::class,
                'queue' => 'mail',
            ]);
            return;
        }

        $createdAt = CarbonImmutable::parse($record->created_at);

        if ($createdAt->lt(now()->subDays(self::VALID_FOR_DAYS))) {
            Log::warning('job.invitation_email.skipped_expired_token', [
                'job' => self::class,
                'queue' => 'mail',
            ]);
            return;
        }

        if ($record->email !== $this->email) {
            Log::warning('job.invitation_email.skipped_email_mismatch', [
                'job' => self::class,
                'queue' => 'mail',
            ]);
            return;
        }

        Mail::to($this->email)->send(new UserInvitationMail($this->token));
        Log::info('job.invitation_email.sent', [
            'job' => self::class,
            'queue' => 'mail',
        ]);
    }
}
