<?php

namespace App\Data\Events;

use App\Models\Event;

class EventData
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly string $occursAt,
        public readonly string $createdAt,
        public readonly string $updatedAt,
    ) {
    }

    public static function fromModel(Event $event): self
    {
        return new self(
            id: $event->id,
            title: $event->title,
            description: $event->description,
            occursAt: (string) $event->occurs_at?->toIso8601String(),
            createdAt: (string) $event->created_at?->toIso8601String(),
            updatedAt: (string) $event->updated_at?->toIso8601String(),
        );
    }

    /**
     * @return array{id:int,title:string,description:string|null,occurs_at:string,created_at:string,updated_at:string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'occurs_at' => $this->occursAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}

