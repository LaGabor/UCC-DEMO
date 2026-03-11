<?php

namespace App\Contracts\Services;

use App\Data\Events\EventFiltersData;
use App\Data\Events\EventData;
use App\Data\Events\EventListData;
use App\Data\Events\UpsertEventData;
use App\Data\Events\UpdateEventDescriptionData;

interface EventServiceInterface
{
    public function listForUser(int $userId, EventFiltersData $filters): EventListData;

    public function createForUser(int $userId, UpsertEventData $data): EventData;

    public function getForUser(int $userId, int $eventId): EventData;

    public function updateForUser(int $userId, int $eventId, UpdateEventDescriptionData $data): EventData;

    public function deleteForUser(int $userId, int $eventId): void;
}

