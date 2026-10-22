<?php

namespace App\Enums;

/**
 * Status paket dihitung dari held_by_owner_at dan collected_at, tidak disimpan di database.
 */
enum ParcelStatus: string
{
    case Waiting = 'waiting';
    case AtOwner = 'at_owner';
    case PickedUp = 'picked_up';
    case ReceivedDirectly = 'received_directly';
}
