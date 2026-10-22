<?php

namespace App\Enums;

enum ComplaintStatus: string
{
    case Reported = 'reported';
    case InProgress = 'in_progress';
    case Resolved = 'resolved';
}
