<?php

namespace App\Enums;

enum RentalInquiryStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case Done = 'done';
}
