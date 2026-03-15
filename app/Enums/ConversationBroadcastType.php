<?php

namespace App\Enums;

enum ConversationBroadcastType: string
{
    case STATUS_CHANGE = 'status_change';
    case STATUS_CHANGE_OBJECT = 'status_change_object';
    case MESSAGE = 'message';
}
