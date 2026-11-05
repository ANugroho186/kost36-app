import { Badge } from '@/components/ui/badge';

// Sama dengan App\Enums\ParcelStatus. Dihitung server dari held_by_owner_at dan collected_at.
export type ParcelStatus =
    | 'waiting'
    | 'at_owner'
    | 'picked_up'
    | 'received_directly';

// Kalimatnya mengikuti siapa yang membaca: penghuni pemilik paket atau pemilik kost.
const labels: Record<'tenant' | 'owner', Record<ParcelStatus, string>> = {
    tenant: {
        waiting: 'On the way',
        at_owner: "At the owner's place",
        picked_up: 'Picked up',
        received_directly: 'Received',
    },
    owner: {
        waiting: 'On the way',
        at_owner: 'At your place',
        picked_up: 'Picked up',
        received_directly: 'Received by tenant',
    },
};

export function ParcelStatusBadge({
    status,
    audience,
}: {
    status: ParcelStatus;
    audience: 'tenant' | 'owner';
}) {
    return (
        <Badge
            variant={
                status === 'at_owner'
                    ? 'default'
                    : status === 'waiting'
                      ? 'outline'
                      : 'secondary'
            }
        >
            {labels[audience][status]}
        </Badge>
    );
}
