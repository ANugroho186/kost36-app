<?php

namespace App\Enums;

enum NotifyChannel: string
{
    case Telegram = 'telegram';
    case Email = 'email';
}
