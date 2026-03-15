<?php

namespace App\Data\Communication;

class CloseUserCommunicationResultData
{
    public function __construct(
        public readonly array $closed,
    ) {
    }

    public function toArray(): array
    {
        return [
            'closed' => $this->closed,
        ];
    }
}
