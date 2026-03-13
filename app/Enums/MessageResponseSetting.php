<?php

namespace App\Enums;

enum MessageResponseSetting: string
{
    case HYBRID = 'hybrid';
    case LLM = 'llm';
    case SYSTEM_BOT = 'system_bot';
}
