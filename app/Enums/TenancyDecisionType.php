<?php

namespace App\Enums;

enum TenancyDecisionType: string
{
    case Extend = 'extend';
    case Deactivate = 'deactivate';
    case Reactivate = 'reactivate';
    case PostponeVacate = 'postpone_vacate';
    case End = 'end';
}
