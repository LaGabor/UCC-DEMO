<?php

namespace App\Contracts\Repositories;

use App\Data\Events\EventFiltersData;
use App\Data\Events\UpsertEventData;
use App\Data\Events\UpdateEventDescriptionData;
use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface EventRepositoryInterface
{
    public function paginateByUserId(int $userId, EventFiltersData $filters): LengthAwarePaginator;

    public function createForUserId(int $userId, UpsertEventData $data): Event;

    public function findByIdAndUserId(int $eventId, int $userId): ?Event;

    public function updateDescription(Event $event, UpdateEventDescriptionData $data): Event;

    public function delete(Event $event): void;
}

