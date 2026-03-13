<?php

namespace App\Data\Communication;

use App\Enums\MessageStrength;

readonly class MessageDecisionData
{
    public function __construct(
        public MessageStrength $strength,
        public ?string $message,
    ) {
    }
}
