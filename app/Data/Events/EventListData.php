<?php

namespace App\Data\Events;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EventListData
{
    /**
     * @param array<int, EventData> $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $perPage,
        public readonly int $total,
    ) {
    }

    public static function fromPaginator(LengthAwarePaginator $paginator): self
    {
        /** @var array<int, EventData> $items */
        $items = collect($paginator->items())
            ->map(static fn ($event) => EventData::fromModel($event))
            ->all();

        return new self(
            items: $items,
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            perPage: $paginator->perPage(),
            total: $paginator->total(),
        );
    }

    /**
     * @return array{
     *   data: array<int, array{id:int,title:string,description:string|null,occurs_at:string,created_at:string,updated_at:string}>,
     *   meta: array{current_page:int,last_page:int,per_page:int,total:int}
     * }
     */
    public function toArray(): array
    {
        return [
            'data' => array_map(static fn (EventData $item) => $item->toArray(), $this->items),
            'meta' => [
                'current_page' => $this->currentPage,
                'last_page' => $this->lastPage,
                'per_page' => $this->perPage,
                'total' => $this->total,
            ],
        ];
    }
}

