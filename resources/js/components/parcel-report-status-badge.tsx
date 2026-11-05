import { Badge } from '@/components/ui/badge';

// Sama dengan App\Enums\ParcelReportStatus. Dihitung server dari parcel_id dan resolved_at.
export type ParcelReportStatus = 'open' | 'linked' | 'resolved';

// Kalimatnya mengikuti siapa yang membaca: penghuni pelapor atau pemilik kost.
const labels: Record<'tenant' | 'owner', Record<ParcelReportStatus, string>> = {
    tenant: {
        open: 'Sent to the owner',
        linked: 'Being handled',
        resolved: 'Resolved',
    },
    owner: {
        open: 'Needs matching',
        linked: 'Matched',
        resolved: 'Resolved',
    },
};

export function ParcelReportStatusBadge({
    status,
    audience,
}: {
    status: ParcelReportStatus;
    audience: 'tenant' | 'owner';
}) {
    return (
        <Badge
            variant={
                status === 'open'
                    ? 'default'
                    : status === 'linked'
                      ? 'outline'
                      : 'secondary'
            }
        >
            {labels[audience][status]}
        </Badge>
    );
}
