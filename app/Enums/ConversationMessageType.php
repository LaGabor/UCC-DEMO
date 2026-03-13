<?php

namespace App\Enums;

enum ConversationMessageType: string
{
    case QUESTION = 'question';
    case BOT_ANSWER = 'bot_answer';
    case AGENT_ANSWER = 'agent_answer';
    case SYSTEM_NOTICE = 'system_notice';
    case SYSTEM_ERROR = 'system_error';
}
