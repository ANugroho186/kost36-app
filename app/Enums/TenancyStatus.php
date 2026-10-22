<?php

namespace App\Enums;

enum TenancyStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Ended = 'ended';
}
