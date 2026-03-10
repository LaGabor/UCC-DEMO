<?php

namespace App\Enums;

enum PasswordResetTokenType: string
{
    case INVITATION = 'invitation';
    case PASSWORD_RESET = 'password_reset';
}
