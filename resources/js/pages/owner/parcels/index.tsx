import { Form, Head } from '@inertiajs/react';
import ParcelController from '@/actions/App/Http/Controllers/Owner/ParcelController';
import ParcelReportController from '@/actions/App/Http/Controllers/Owner/ParcelReportController';
import { FormDialog } from '@/components/form-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import type { ParcelReportStatus } from '@/components/parcel-report-status-badge';
import { ParcelReportStatusBadge } from '@/components/parcel-report-status-badge';
import type { ParcelStatus } from '@/components/parcel-status-badge';
import { ParcelStatusBadge } from '@/components/parcel-status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDate } from '@/lib/dates';
import { index } from '@/routes/owner/parcels';

// Sama persis dengan yang dikirim Owner\ParcelController::index(). Sengaja tanpa nama/isi barang.
type OwnerParcel = {
    id: number;
    trackingNumber: string;
    recipient: string;
    roomNumber: string | null;
    expectedDate: string | null;
    status: ParcelStatus;
};

type OwnerParcelReport = {
    id: number;
    description: string;
    reportedAt: string | null;
    status: ParcelReportStatus;
    // Penghuni yang sedang memegang paket itu. Null kalau akunnya sudah dihapus.
    reporter: string | null;
    reporterRoom: string | null;
    parcelId: number | null;
    // Paket yang resinya tertulis di keterangan laporan, kalau ada.
    suggestedParcelId: number | null;
};

type Props = {
    parcels: OwnerParcel[];
    reports: OwnerParcelReport[];
};

function describeParcel(parcel: OwnerParcel): string {
    return `${parcel.trackingNumber} · ${parcel.recipient}${parcel.roomNumber !== null ? `, room ${parcel.roomNumber}` : ''}`;
}

function LinkReportDialog({
    report,
    parcels,
}: {
    report: OwnerParcelReport;
    parcels: OwnerParcel[];
}) {
    // Hanya paket yang belum diterima. Server mengecek hal yang sama.
    const choices = parcels.filter(
        (parcel) => parcel.status === 'waiting' || parcel.status === 'at_owner',
    );
    const preselected = report.parcelId ?? report.suggestedParcelId;

    return (
        <FormDialog
            trigger={
                <Button size="sm" variant="outline">
                    {report.parcelId === null ? 'Match to a parcel' : 'Change'}
                </Button>
            }
            title="Whose parcel is it?"
            description="The parcel owner will see that their parcel was received by mistake, without the reporter's name."
            action={ParcelReportController.link.form(report.id)}
            submitLabel="Match"
        >
            {({ errors }) => (
                <div className="grid gap-2">
                    <Label htmlFor="parcel_id">Parcel</Label>
                    <Select
                        name="parcel_id"
                        required
                        defaultValue={
                            preselected !== null
                                ? String(preselected)
                                : undefined
                        }
                    >
                        <SelectTrigger id="parcel_id" className="w-full">
                            <SelectValue placeholder="Choose a parcel" />
                        </SelectTrigger>
                        <SelectContent>
                            {choices.map((parcel) => (
                                <SelectItem
                                    key={parcel.id}
                                    value={String(parcel.id)}
                                >
                                    {describeParcel(parcel)}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    {report.suggestedParcelId !== null && (
                        <p className="text-sm text-muted-foreground">
                            Suggested because its tracking number appears in the
                            report.
                        </p>
                    )}
                    <InputError message={errors.parcel_id} />
                </div>
            )}
        </FormDialog>
    );
}

function ReportCard({
    report,
    parcels,
}: {
    report: OwnerParcelReport;
    parcels: OwnerParcel[];
}) {
    const linked = parcels.find((parcel) => parcel.id === report.parcelId);

    return (
        <Card className={report.status === 'resolved' ? 'opacity-70' : ''}>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle className="text-base">
                        {report.description}
                    </CardTitle>
                    <ParcelReportStatusBadge
                        status={report.status}
                        audience="owner"
                    />
                </div>
                <CardDescription>
                    Received by {report.reporter ?? 'a former tenant'}
                    {report.reporterRoom !== null &&
                        ` · room ${report.reporterRoom}`}
                    {report.reportedAt !== null &&
                        ` · reported ${formatDate(report.reportedAt)}`}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
                {linked !== undefined && (
                    <p>Belongs to: {describeParcel(linked)}</p>
                )}
                {report.status !== 'resolved' && (
                    <div className="flex flex-wrap gap-2">
                        <LinkReportDialog report={report} parcels={parcels} />
                        <FormDialog
                            trigger={<Button size="sm">Resolve</Button>}
                            title="Mark this report as resolved?"
                            description="Do this once the parcel is back with its owner, or after you sorted it out outside the app."
                            action={ParcelReportController.resolve.form(
                                report.id,
                            )}
                            submitLabel="Mark as resolved"
                        />
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function ParcelCard({ parcel }: { parcel: OwnerParcel }) {
    const isDone =
        parcel.status === 'picked_up' || parcel.status === 'received_directly';

    return (
        <Card className={isDone ? 'opacity-70' : ''}>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle className="font-mono">
                        {parcel.trackingNumber}
                    </CardTitle>
                    <ParcelStatusBadge
                        status={parcel.status}
                        audience="owner"
                    />
                </div>
                <CardDescription>
                    For {parcel.recipient}
                    {parcel.roomNumber !== null &&
                        ` · room ${parcel.roomNumber}`}
                    {parcel.expectedDate !== null &&
                        ` · expected ${formatDate(parcel.expectedDate)}`}
                </CardDescription>
            </CardHeader>
            {parcel.status === 'waiting' && (
                <CardContent>
                    {/* Satu klik, tanpa isian: menandai paket sudah diterima pemilik kost. */}
                    <Form
                        {...ParcelController.markHeld.form(parcel.id)}
                        options={{ preserveScroll: true }}
                    >
                        {({ processing }) => (
                            <Button size="sm" disabled={processing}>
                                It&apos;s at my place
                            </Button>
                        )}
                    </Form>
                </CardContent>
            )}
        </Card>
    );
}

export default function OwnerParcels({ parcels, reports }: Props) {
    return (
        <>
            <Head title="Parcels" />

            <div className="max-w-3xl space-y-6 p-4">
                <Heading
                    title="Parcels"
                    description="Parcels your tenants are expecting. What is inside stays private to each tenant."
                />

                {reports.length > 0 && (
                    <section className="space-y-4">
                        <Heading
                            variant="small"
                            title="Wrong-recipient reports"
                            description="Tenants who received a parcel that is not theirs."
                        />
                        {reports.map((report) => (
                            <ReportCard
                                key={report.id}
                                report={report}
                                parcels={parcels}
                            />
                        ))}
                    </section>
                )}

                {parcels.length === 0 ? (
                    <p className="text-muted-foreground">
                        No parcels registered.
                    </p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {parcels.map((parcel) => (
                            <ParcelCard key={parcel.id} parcel={parcel} />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

OwnerParcels.layout = {
    breadcrumbs: [
        {
            title: 'Parcels',
            href: index(),
        },
    ],
};
