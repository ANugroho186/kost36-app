<?php

namespace App\Enums;

enum InventoryCondition: string
{
    case Good = 'good';
    case NeedsRepair = 'needs_repair';
    case Broken = 'broken';
}
