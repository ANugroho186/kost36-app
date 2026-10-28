import { Head, Link } from '@inertiajs/react';
import type { ComplaintStatus } from '@/components/complaint-status-badge';
import { ComplaintStatusBadge } from '@/components/complaint-status-badge';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate } from '@/lib/dates';
import { create, index } from '@/routes/complaints';

// Sama persis dengan yang dikirim ComplaintController::index().
type TenantComplaint = {
    id: number;
    title: string;
    description: string;
    status: ComplaintStatus;
    reportedAt: string | null;
    resolvedAt: string | null;
    photos: { id: number; url: string }[];
};

type Props = {
    roomNumber: string | null;
    complaints: TenantComplaint[];
};

export default function TenantComplaints({ roomNumber, complaints }: Props) {
    return (
        <>
            <Head title="Complaints" />

            <div className="max-w-2xl space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Complaints"
                        description={
                            roomNumber === null
                                ? undefined
                                : `Problems reported for room ${roomNumber}, by you or your roommate.`
                        }
                    />
                    {roomNumber !== null && (
                        <Button asChild>
                            <Link href={create()}>Report a problem</Link>
                        </Button>
                    )}
                </div>

                {roomNumber === null ? (
                    <p className="text-muted-foreground">
                        Your account is not linked to a room right now.
                    </p>
                ) : complaints.length === 0 ? (
                    <p className="text-muted-foreground">
                        No complaints yet. If something in your room is broken,
                        let the owner know here.
                    </p>
                ) : (
                    complaints.map((complaint) => (
                        <Card key={complaint.id}>
                            <CardHeader>
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <CardTitle>{complaint.title}</CardTitle>
                                    <ComplaintStatusBadge
                                        status={complaint.status}
                                    />
                                </div>
                                {complaint.reportedAt !== null && (
                                    <CardDescription>
                                        Reported{' '}
                                        {formatDate(complaint.reportedAt)}
                                        {complaint.resolvedAt !== null &&
                                            ` · resolved ${formatDate(complaint.resolvedAt)}`}
                                    </CardDescription>
                                )}
                            </CardHeader>
                            <CardContent className="space-y-4 text-sm">
                                <p className="whitespace-pre-line">
                                    {complaint.description}
                                </p>
                                {complaint.photos.length > 0 && (
                                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                        {complaint.photos.map(
                                            (photo, photoIndex) => (
                                                <a
                                                    key={photo.id}
                                                    href={photo.url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                >
                                                    <img
                                                        src={photo.url}
                                                        alt={`Photo ${photoIndex + 1} of “${complaint.title}”`}
                                                        className="aspect-square w-full rounded-md border object-cover"
                                                    />
                                                </a>
                                            ),
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>
        </>
    );
}

TenantComplaints.layout = {
    breadcrumbs: [
        {
            title: 'Complaints',
            href: index(),
        },
    ],
};
