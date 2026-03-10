<?php

namespace App\Enums;

enum ConversationMessageSenderType: string
{
    case USER = 'user';
    case BOT = 'bot';
    case AGENT = 'agent';
    case SYSTEM = 'system';
}
