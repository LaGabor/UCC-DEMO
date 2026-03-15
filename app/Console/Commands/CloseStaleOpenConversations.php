<?php

namespace App\Console\Commands;

use App\Contracts\Repositories\UserCommunicationRepositoryInterface;
use App\Enums\ConversationStatus;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class CloseStaleOpenConversations extends Command
{
    protected $signature = 'communication:close-stale-open-conversations';

    protected $description = 'Close non-closed conversations with no message in the last 5 minutes.';

    private const STALE_MINUTES = 5;

    public function __construct(
        private readonly UserCommunicationRepositoryInterface $userCommunicationRepository,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $before = CarbonImmutable::now()->subMinutes(self::STALE_MINUTES);
        $conversations = $this->userCommunicationRepository->findNonClosedStaleConversations($before);
        $closed = new Collection;

        foreach ($conversations as $conversation) {
            try {
                $conversation = $this->userCommunicationRepository->updateConversationStatus(
                    $conversation,
                    ConversationStatus::CLOSED
                );
                $closed->push($conversation);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($closed->isNotEmpty()) {
            $this->info('Closed ' . $closed->count() . ' stale conversation(s).');
        }

        return self::SUCCESS;
    }
}
