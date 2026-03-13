<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Enums\ConversationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

class CloseStaleOpenConversations extends Command
{
    protected $signature = 'communication:close-stale-open-conversations';

    protected $description = 'Close open conversations whose last_message_at is older than 5 minutes.';

    private const STALE_MINUTES = 5;

    public function __construct(
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $before = CarbonImmutable::now()->subMinutes(self::STALE_MINUTES);
        $conversations = $this->userCommunicationRepository->findOpenConversationsWithLastMessageBefore($before);

        foreach ($conversations as $conversation) {
            $conversation = $this->userCommunicationRepository->updateConversationStatus(
                $conversation,
                ConversationStatus::CLOSED
            );
            try {
                broadcast(new \App\Events\ConversationStatusBroadcasted($conversation));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($conversations->isNotEmpty()) {
            $this->info('Closed ' . $conversations->count() . ' stale open conversation(s).');
        }

        return self::SUCCESS;
    }
}
