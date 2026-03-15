<?php

namespace App\Services;

use App\Contracts\Repositories\EventRepositoryInterface;
use App\Contracts\Services\EventServiceInterface;
use App\Data\Events\EventData;
use App\Data\Events\EventFiltersData;
use App\Data\Events\EventListData;
use App\Data\Events\UpsertEventData;
use App\Data\Events\UpdateEventDescriptionData;
use App\Models\Event;

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

    public function get(Event $event): EventData
    {
        return EventData::fromModel($event);
    }

    public function update(Event $event, UpdateEventDescriptionData $data): EventData
    {
        $updated = $this->eventRepository->updateDescription($event, $data);

        return EventData::fromModel($updated);
    }

    public function delete(Event $event): void
    {
        $this->eventRepository->delete($event);
    }
}

