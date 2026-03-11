<?php

namespace App\Data\Events;

use App\Http\Requests\API\Events\IndexEventRequest;

class EventFiltersData
{
    public function __construct(
        public readonly ?string $query,
        public readonly ?string $from,
        public readonly ?string $to,
        public readonly int $page,
        public readonly int $perPage,
        public readonly string $sortBy,
        public readonly string $sortDirection,
    ) {
    }

    public static function fromRequest(IndexEventRequest $request): self
    {
        $q = $request->string('q')->toString();
        $from = $request->input('from');
        $to = $request->input('to');

        return new self(
            query: $q !== '' ? $q : null,
            from: is_string($from) && $from !== '' ? $from : null,
            to: is_string($to) && $to !== '' ? $to : null,
            page: max(1, $request->integer('page', 1)),
            perPage: $request->integer('per_page', 10),
            sortBy: $request->string('sort_by')->toString(),
            sortDirection: $request->string('sort_dir')->toString(),
        );
    }
}

