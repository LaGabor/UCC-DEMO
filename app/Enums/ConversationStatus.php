<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case OPEN = 'open';
    case WAITING_HUMAN = 'waiting_human';
    case ASSIGNED = 'assigned';
    case CLOSED = 'closed';
}
