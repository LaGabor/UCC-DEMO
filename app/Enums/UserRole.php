<?php

namespace App\Enums;

enum UserRole: string
{
    case USER = 'user';
    case HELPDESK_AGENT = 'helpdesk_agent';
    case ADMIN = 'admin';
}
