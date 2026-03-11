<?php

namespace App\Services;

use App\Contracts\Repositories\EventRepositoryInterface;
use App\Contracts\Services\EventServiceInterface;
use App\Data\Events\EventData;
use App\Data\Events\EventFiltersData;
use App\Data\Events\EventListData;
use App\Data\Events\UpsertEventData;
use App\Data\Events\UpdateEventDescriptionData;
use App\Enums\ApiDomainErrorCode;
use App\Enums\ApiDomainStatus;
use App\Exceptions\ApiDomainException;
use Illuminate\Support\Facades\Log;

class EventService implements EventServiceInterface
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
    ) {
    }

    public function listForUser(int $userId, EventFiltersData $filters): EventListData
    {
        $paginator = $this->eventRepository->paginateByUserId($userId, $filters);

        return EventListData::fromPaginator($paginator);
    }

    public function createForUser(int $userId, UpsertEventData $data): EventData
    {
        $event = $this->eventRepository->createForUserId($userId, $data);

        return EventData::fromModel($event);
    }

    public function getForUser(int $userId, int $eventId): EventData
    {
        $event = $this->resolveOwnedEvent($userId, $eventId);

        return EventData::fromModel($event);
    }

    public function updateForUser(int $userId, int $eventId, UpdateEventDescriptionData $data): EventData
    {
        $event = $this->resolveOwnedEvent($userId, $eventId);
        $updated = $this->eventRepository->updateDescription($event, $data);

        return EventData::fromModel($updated);
    }

    public function deleteForUser(int $userId, int $eventId): void
    {
        $event = $this->resolveOwnedEvent($userId, $eventId);
        $this->eventRepository->delete($event);
    }

    private function resolveOwnedEvent(int $userId, int $eventId): \App\Models\Event
    {
        $event = $this->eventRepository->findByIdAndUserId($eventId, $userId);

        if (! $event) {
            Log::warning('event.not_found_or_not_owned', [
                'user_id' => $userId,
                'event_id' => $eventId,
            ]);
            throw new ApiDomainException(
                ApiDomainErrorCode::EVENT_NOT_FOUND,
                'Event not found.',
                null,
                ApiDomainStatus::NOT_FOUND
            );
        }

        return $event;
    }
}

