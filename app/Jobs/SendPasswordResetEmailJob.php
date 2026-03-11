<?php

namespace App\Jobs;

use App\Contracts\Repositories\PasswordResetRepositoryInterface;
use App\Mail\PasswordResetMail;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetEmailJob implements ShouldQueue
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

    public function handle(PasswordResetRepositoryInterface $passwordResetRepository): void
    {
        $record = $passwordResetRepository->findPasswordResetTokenByToken($this->token);

        if (! $record || ! $record->created_at) {
            Log::warning('job.password_reset_email.skipped_missing_or_invalid_token', [
                'job' => self::class,
                'queue' => 'mail',
            ]);
            return;
        }

        $createdAt = CarbonImmutable::parse($record->created_at);

        if ($createdAt->lt(now()->subDays(self::VALID_FOR_DAYS))) {
            Log::warning('job.password_reset_email.skipped_expired_token', [
                'job' => self::class,
                'queue' => 'mail',
            ]);
            return;
        }

        if ($record->email !== $this->email) {
            Log::warning('job.password_reset_email.skipped_email_mismatch', [
                'job' => self::class,
                'queue' => 'mail',
            ]);
            return;
        }

        Mail::to($this->email)->send(new PasswordResetMail($this->token));
    }
}
