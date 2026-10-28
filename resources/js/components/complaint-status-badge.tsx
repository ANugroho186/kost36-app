import { Badge } from '@/components/ui/badge';

// Sama dengan App\Enums\ComplaintStatus.
export type ComplaintStatus = 'reported' | 'in_progress' | 'resolved';

const labels: Record<ComplaintStatus, string> = {
    reported: 'Reported',
    in_progress: 'In progress',
    resolved: 'Resolved',
};

export function ComplaintStatusBadge({ status }: { status: ComplaintStatus }) {
    return (
        <Badge
            variant={
                status === 'resolved'
                    ? 'secondary'
                    : status === 'in_progress'
                      ? 'default'
                      : 'outline'
            }
        >
            {labels[status]}
        </Badge>
    );
}
