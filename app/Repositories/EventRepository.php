<?php

namespace App\Repositories;

use App\Contracts\Repositories\EventRepositoryInterface;
use App\Data\Events\EventFiltersData;
use App\Data\Events\UpsertEventData;
use App\Data\Events\UpdateEventDescriptionData;
use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EventRepository implements EventRepositoryInterface
{
    public function paginateByUserId(int $userId, EventFiltersData $filters): LengthAwarePaginator
    {
        $query = Event::query()
            ->where('user_id', $userId);

        if ($filters->query !== null) {
            $this->applySearch($query, $filters->query);
        }

        if ($filters->from !== null) {
            $query->where('occurs_at', '>=', $filters->from);
        }

        if ($filters->to !== null) {
            $query->where('occurs_at', '<=', $filters->to);
        }

        return $query
            ->orderBy($filters->sortBy, $filters->sortDirection)
            ->paginate($filters->perPage, ['*'], 'page', $filters->page)
            ->withQueryString();
    }

    public function createForUserId(int $userId, UpsertEventData $data): Event
    {
        return Event::query()->create([
            'user_id' => $userId,
            'title' => $data->title,
            'description' => $data->description,
            'occurs_at' => $data->occursAt,
        ]);
    }

    public function findById(int $eventId): ?Event
    {
        return Event::query()->find($eventId);
    }

    public function findByIdAndUserId(int $eventId, int $userId): ?Event
    {
        return Event::query()
            ->where('id', $eventId)
            ->where('user_id', $userId)
            ->first();
    }

    public function updateDescription(Event $event, UpdateEventDescriptionData $data): Event
    {
        $event->fill([
            'description' => $data->description,
        ])->save();

        return $event->fresh();
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }

    private function applySearch(Builder $query, string $search): void
    {
        $driver = $query->getConnection()->getDriverName();
        $supportsFullText = in_array($driver, ['mysql', 'mariadb', 'pgsql'], true);
        $escapedSearch = str_replace(['%', '_'], ['\\%', '\\_'], $search);
        $like = '%' . $escapedSearch . '%';

        $query->where(function (Builder $subQuery) use ($supportsFullText, $search, $like): void {
            if ($supportsFullText) {
                $subQuery->whereFullText(['title', 'description'], $search);
            } else {
                $subQuery
                    ->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like);
            }
        });
    }
}

