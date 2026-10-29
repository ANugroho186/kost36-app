import { Head } from '@inertiajs/react';
import ComplaintController from '@/actions/App/Http/Controllers/ComplaintController';
import type { ComplaintStatus } from '@/components/complaint-status-badge';
import { ComplaintStatusBadge } from '@/components/complaint-status-badge';
import { FormDialog } from '@/components/form-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/dates';
import { firstErrorFor } from '@/lib/form-errors';
import { index } from '@/routes/complaints';

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
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="title">What is the problem?</Label>
                        <Input
                            id="title"
                            name="title"
                            required
                            maxLength={100}
                            placeholder="Leaking tap"
                        />
                        <InputError message={errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Details</Label>
                        <Textarea
                            id="description"
                            name="description"
                            required
                            rows={4}
                            maxLength={2000}
                            placeholder="Where it is, since when, and how bad it is"
                        />
                        <InputError message={errors.description} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="photos">Photos (optional)</Label>
                        <Input
                            id="photos"
                            name="photos[]"
                            type="file"
                            multiple
                            accept="image/jpeg,image/png,image/webp"
                        />
                        <p className="text-sm text-muted-foreground">
                            Up to {maxPhotos} photos, JPG, PNG or WebP, 5 MB
                            each. Only the owner and your roommate can open
                            them. Location data is removed.
                        </p>
                        <InputError message={firstErrorFor(errors, 'photos')} />
                    </div>
                </>
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
