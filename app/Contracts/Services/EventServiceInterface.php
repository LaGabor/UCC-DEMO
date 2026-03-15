<?php

namespace App\Contracts\Services;

use App\Data\Events\EventData;
use App\Data\Events\EventFiltersData;
use App\Data\Events\EventListData;
use App\Data\Events\UpdateEventDescriptionData;
use App\Data\Events\UpsertEventData;
use App\Models\Event;

interface EventServiceInterface
{
    public function listForUser(int $userId, EventFiltersData $filters): EventListData;

    public function createForUser(int $userId, UpsertEventData $data): EventData;

    public function get(Event $event): EventData;

    public function update(Event $event, UpdateEventDescriptionData $data): EventData;

    public function delete(Event $event): void;
}

