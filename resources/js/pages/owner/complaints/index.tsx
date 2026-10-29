import { Form, Head, Link } from '@inertiajs/react';
import ComplaintController from '@/actions/App/Http/Controllers/Owner/ComplaintController';
import { ComplaintFields } from '@/components/complaint-fields';
import type { ComplaintPhoto } from '@/components/complaint-photos';
import { ComplaintPhotos } from '@/components/complaint-photos';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/dates';
import { index } from '@/routes/owner/complaints';

// Sama persis dengan yang dikirim Owner\ComplaintController::index().
type OwnerComplaint = {
    id: number;
    roomNumber: string;
    title: string;
    description: string;
    status: ComplaintStatus;
    reportedAt: string | null;
    resolvedAt: string | null;
    // Null kalau dicatat pemilik atau akun pelapornya sudah dihapus.
    reporter: string | null;
    photos: ComplaintPhoto[];
    repairs: { id: number; performedAt: string; description: string }[];
};

type Filter = 'open' | 'resolved' | 'all';

type Props = {
    complaints: OwnerComplaint[];
    filter: Filter;
    counts: { open: number; resolved: number };
    rooms: { id: number; number: string }[];
    maxPhotos: number;
    today: string;
};

function RecordComplaintDialog({
    rooms,
    maxPhotos,
}: Pick<Props, 'rooms' | 'maxPhotos'>) {
    return (
        <FormDialog
            trigger={<Button>Record a complaint</Button>}
            title="Record a complaint"
            description="For a tenant without a login, for example one who told you in person."
            action={ComplaintController.store.form()}
            submitLabel="Record complaint"
        >
            {({ errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="room_id">Room</Label>
                        <Select name="room_id" required>
                            <SelectTrigger id="room_id" className="w-full">
                                <SelectValue placeholder="Choose a room" />
                            </SelectTrigger>
                            <SelectContent>
                                {rooms.map((room) => (
                                    <SelectItem
                                        key={room.id}
                                        value={String(room.id)}
                                    >
                                        Room {room.number}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.room_id} />
                    </div>
                    <ComplaintFields
                        errors={errors}
                        maxPhotos={maxPhotos}
                        photoNote="Only you and the tenants of that room can open them."
                    />
                </>
            )}
        </FormDialog>
    );
}

// Perubahan status tanpa isian (mulai dikerjakan, buka lagi) cukup satu tombol.
function StatusButton({
    complaintId,
    status,
    label,
}: {
    complaintId: number;
    status: ComplaintStatus;
    label: string;
}) {
    return (
        <Form
            {...ComplaintController.update.form(complaintId)}
            options={{ preserveScroll: true }}
        >
            {({ processing }) => (
                <>
                    <input type="hidden" name="status" value={status} />
                    <Button size="sm" variant="outline" disabled={processing}>
                        {label}
                    </Button>
                </>
            )}
        </Form>
    );
}

function ResolveDialog({
    complaint,
    today,
}: {
    complaint: OwnerComplaint;
    today: string;
}) {
    return (
        <FormDialog
            trigger={<Button size="sm">Resolve</Button>}
            title={`Resolve “${complaint.title}”`}
            description={`Room ${complaint.roomNumber}. A repair note is kept in the maintenance log of the room.`}
            action={ComplaintController.update.form(complaint.id)}
            submitLabel="Mark as resolved"
        >
            {({ errors }) => (
                <>
                    <input type="hidden" name="status" value="resolved" />
                    <div className="grid gap-2">
                        <Label htmlFor="repair_description">
                            What was done (optional)
                        </Label>
                        <Textarea
                            id="repair_description"
                            name="repair_description"
                            rows={3}
                            maxLength={2000}
                            placeholder="Replaced the tap washer"
                        />
                        <InputError message={errors.repair_description} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="repaired_on">Repaired on</Label>
                        <Input
                            id="repaired_on"
                            name="repaired_on"
                            type="date"
                            max={today}
                            defaultValue={today}
                        />
                        <InputError message={errors.repaired_on} />
                    </div>
                </>
            )}
        </FormDialog>
    );
}

function ComplaintCard({
    complaint,
    today,
}: {
    complaint: OwnerComplaint;
    today: string;
}) {
    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle>{complaint.title}</CardTitle>
                    <ComplaintStatusBadge status={complaint.status} />
                </div>
                <CardDescription>
                    Room {complaint.roomNumber} · reported by{' '}
                    {complaint.reporter ?? 'a tenant without a login'}
                    {complaint.reportedAt !== null &&
                        ` on ${formatDate(complaint.reportedAt)}`}
                    {complaint.resolvedAt !== null &&
                        ` · resolved ${formatDate(complaint.resolvedAt)}`}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
                <p className="whitespace-pre-line">{complaint.description}</p>

                <ComplaintPhotos
                    photos={complaint.photos}
                    title={complaint.title}
                />

                {complaint.repairs.length > 0 && (
                    <ul className="space-y-1 border-l-2 pl-3 text-muted-foreground">
                        {complaint.repairs.map((repair) => (
                            <li key={repair.id}>
                                {formatDate(repair.performedAt)}:{' '}
                                {repair.description}
                            </li>
                        ))}
                    </ul>
                )}

                <div className="flex flex-wrap gap-2">
                    {complaint.status === 'reported' && (
                        <StatusButton
                            complaintId={complaint.id}
                            status="in_progress"
                            label="Start work"
                        />
                    )}
                    {complaint.status === 'resolved' ? (
                        <StatusButton
                            complaintId={complaint.id}
                            status="in_progress"
                            label="Reopen"
                        />
                    ) : (
                        <ResolveDialog complaint={complaint} today={today} />
                    )}
                    <FormDialog
                        trigger={
                            <Button size="sm" variant="ghost">
                                Delete
                            </Button>
                        }
                        title={`Delete “${complaint.title}”?`}
                        description="Use this for duplicates or mistakes. Its photos are deleted too, but repair notes stay in the maintenance log."
                        action={ComplaintController.destroy.form(complaint.id)}
                        submitLabel="Delete"
                        destructive
                    />
                </div>
            </CardContent>
        </Card>
    );
}

export default function OwnerComplaints({
    complaints,
    filter,
    counts,
    rooms,
    maxPhotos,
    today,
}: Props) {
    const filters: { value: Filter; label: string }[] = [
        { value: 'open', label: `Open (${counts.open})` },
        { value: 'resolved', label: `Resolved (${counts.resolved})` },
        { value: 'all', label: 'All' },
    ];

    return (
        <>
            <Head title="Complaints" />

            <div className="max-w-3xl space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Complaints"
                        description="Problems reported in every room."
                    />
                    {rooms.length > 0 && (
                        <RecordComplaintDialog
                            rooms={rooms}
                            maxPhotos={maxPhotos}
                        />
                    )}
                </div>

                <div className="flex flex-wrap gap-2">
                    {filters.map((option) => (
                        <Button
                            key={option.value}
                            size="sm"
                            variant={
                                option.value === filter ? 'default' : 'outline'
                            }
                            asChild
                        >
                            <Link
                                href={index({
                                    query: { status: option.value },
                                })}
                                preserveScroll
                            >
                                {option.label}
                            </Link>
                        </Button>
                    ))}
                </div>

                {complaints.length === 0 ? (
                    <p className="text-muted-foreground">
                        {filter === 'open'
                            ? 'No open complaints. Everything is in order.'
                            : 'No complaints here.'}
                    </p>
                ) : (
                    complaints.map((complaint) => (
                        <ComplaintCard
                            key={complaint.id}
                            complaint={complaint}
                            today={today}
                        />
                    ))
                )}
            </div>
        </>
    );
}

OwnerComplaints.layout = {
    breadcrumbs: [
        {
            title: 'Complaints',
            href: index(),
        },
    ],
};
