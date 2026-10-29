import { Head } from '@inertiajs/react';
import ComplaintController from '@/actions/App/Http/Controllers/ComplaintController';
import { ComplaintFields } from '@/components/complaint-fields';
import type { ComplaintStatus } from '@/components/complaint-status-badge';
import type { ComplaintPhoto } from '@/components/complaint-photos';
import { ComplaintPhotos } from '@/components/complaint-photos';
import { ComplaintStatusBadge } from '@/components/complaint-status-badge';
import { FormDialog } from '@/components/form-dialog';
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
import { index } from '@/routes/complaints';

// Sama persis dengan yang dikirim ComplaintController::index().
type TenantComplaint = {
    id: number;
    title: string;
    description: string;
    status: ComplaintStatus;
    reportedAt: string | null;
    resolvedAt: string | null;
    photos: ComplaintPhoto[];
};

type Props = {
    roomNumber: string | null;
    complaints: TenantComplaint[];
    canReport: boolean;
    maxPhotos: number;
};

function ReportComplaintDialog({
    roomNumber,
    maxPhotos,
}: {
    roomNumber: string;
    maxPhotos: number;
}) {
    return (
        <FormDialog
            trigger={<Button>Report a problem</Button>}
            title="Report a problem"
            description={`For room ${roomNumber}. The owner and your roommate can see this complaint.`}
            action={ComplaintController.store.form()}
            submitLabel="Send complaint"
        >
            {({ errors }) => (
                <ComplaintFields
                    errors={errors}
                    maxPhotos={maxPhotos}
                    photoNote="Only the owner and your roommate can open them."
                />
            )}
        </FormDialog>
    );
}

export default function TenantComplaints({
    roomNumber,
    complaints,
    canReport,
    maxPhotos,
}: Props) {
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
                    {canReport && roomNumber !== null && (
                        <ReportComplaintDialog
                            roomNumber={roomNumber}
                            maxPhotos={maxPhotos}
                        />
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
                                <ComplaintPhotos
                                    photos={complaint.photos}
                                    title={complaint.title}
                                />
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
