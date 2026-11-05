import { Form, Head } from '@inertiajs/react';
import ParcelController from '@/actions/App/Http/Controllers/ParcelController';
import ParcelReportController from '@/actions/App/Http/Controllers/ParcelReportController';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDate } from '@/lib/dates';
import { index } from '@/routes/parcels';

// Sama persis dengan yang dikirim ParcelController::index(): hanya paket milik penghuni ini.
type TenantParcel = {
    id: number;
    trackingNumber: string;
    itemName: string | null;
    expectedDate: string | null;
    status: ParcelStatus;
    // Paket ini sampai ke penghuni lain dan sedang diurus pemilik kost. Pelapornya tidak disebut.
    misdelivered: boolean;
};

// Laporan salah terima yang dibuat penghuni ini. Hanya status, tanpa paket yang ditautkan.
type TenantParcelReport = {
    id: number;
    description: string;
    reportedAt: string | null;
    status: ParcelReportStatus;
};

type Props = {
    parcels: TenantParcel[];
    canRegister: boolean;
    reports: TenantParcelReport[];
    canReport: boolean;
};

// Kolom form paket untuk modal daftar dan ubah. Nama field mengikuti ParcelRequest di server.
function ParcelFields({
    parcel,
    errors,
}: {
    parcel?: TenantParcel;
    errors: Partial<Record<string, string>>;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="tracking_number">Tracking number</Label>
                <Input
                    id="tracking_number"
                    name="tracking_number"
                    required
                    maxLength={50}
                    autoCapitalize="characters"
                    autoComplete="off"
                    className="font-mono"
                    defaultValue={parcel?.trackingNumber}
                />
                <InputError message={errors.tracking_number} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="item_name">What is inside? (optional)</Label>
                <Input
                    id="item_name"
                    name="item_name"
                    maxLength={100}
                    defaultValue={parcel?.itemName ?? undefined}
                    placeholder="Phone charger"
                />
                <p className="text-sm text-muted-foreground">
                    Only you can see this. The owner only sees the tracking
                    number, your name and your room.
                </p>
                <InputError message={errors.item_name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="expected_date">Expected on (optional)</Label>
                <Input
                    id="expected_date"
                    name="expected_date"
                    type="date"
                    defaultValue={parcel?.expectedDate ?? undefined}
                />
                <InputError message={errors.expected_date} />
            </div>
        </>
    );
}

// "Sudah saya terima": satu klik, tanpa isian.
function MarkCollectedButton({
    parcelId,
    label,
}: {
    parcelId: number;
    label: string;
}) {
    return (
        <Form
            {...ParcelController.markCollected.form(parcelId)}
            options={{ preserveScroll: true }}
        >
            {({ processing }) => (
                <Button size="sm" disabled={processing}>
                    {label}
                </Button>
            )}
        </Form>
    );
}

// Tombol mengikuti status. Server tetap mengecek setiap aksi lewat ParcelPolicy.
function ParcelActions({ parcel }: { parcel: TenantParcel }) {
    if (parcel.status === 'at_owner') {
        return (
            <MarkCollectedButton
                parcelId={parcel.id}
                label="I've picked it up"
            />
        );
    }

    if (parcel.status !== 'waiting') {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-2">
            <MarkCollectedButton parcelId={parcel.id} label="I've got it" />
            <FormDialog
                trigger={
                    <Button size="sm" variant="outline">
                        Edit
                    </Button>
                }
                title="Edit parcel"
                action={ParcelController.update.form(parcel.id)}
                submitLabel="Save"
            >
                {({ errors }) => (
                    <ParcelFields parcel={parcel} errors={errors} />
                )}
            </FormDialog>
            <FormDialog
                trigger={
                    <Button size="sm" variant="ghost">
                        Cancel
                    </Button>
                }
                title={`Remove parcel ${parcel.trackingNumber}?`}
                description="Use this when the order was cancelled. The owner will no longer see it."
                action={ParcelController.destroy.form(parcel.id)}
                submitLabel="Remove parcel"
                destructive
            />
        </div>
    );
}

function ReportDialog() {
    return (
        <FormDialog
            trigger={
                <Button variant="outline">
                    I received someone else&apos;s parcel
                </Button>
            }
            title="Report a parcel that is not yours"
            description="Keep the parcel safe. The owner will find out whose it is and arrange for it to reach them."
            action={ParcelReportController.store.form()}
            submitLabel="Send report"
        >
            {({ errors }) => (
                <div className="grid gap-2">
                    <Label htmlFor="description">
                        Tracking number or name on the label
                    </Label>
                    <Textarea
                        id="description"
                        name="description"
                        required
                        rows={3}
                        maxLength={500}
                        placeholder="JNE123ABC, for Rina"
                    />
                    <p className="text-sm text-muted-foreground">
                        The owner of the parcel is not told who reported it.
                    </p>
                    <InputError message={errors.description} />
                </div>
            )}
        </FormDialog>
    );
}

export default function TenantParcels({
    parcels,
    canRegister,
    reports,
    canReport,
}: Props) {
    return (
        <>
            <Head title="Parcels" />

            <div className="max-w-2xl space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Parcels"
                        description="Let the owner know a parcel is coming, so they can receive it if you are out."
                    />
                    {canRegister && (
                        <FormDialog
                            trigger={<Button>Register a parcel</Button>}
                            title="Register a parcel"
                            description="Only you see this list. Your roommate does not."
                            action={ParcelController.store.form()}
                            submitLabel="Register parcel"
                        >
                            {({ errors }) => <ParcelFields errors={errors} />}
                        </FormDialog>
                    )}
                </div>

                {parcels.length === 0 ? (
                    <p className="text-muted-foreground">
                        No parcels registered.
                    </p>
                ) : (
                    parcels.map((parcel) => (
                        <Card key={parcel.id}>
                            <CardHeader>
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <CardTitle className="font-mono">
                                        {parcel.trackingNumber}
                                    </CardTitle>
                                    <ParcelStatusBadge
                                        status={parcel.status}
                                        audience="tenant"
                                    />
                                </div>
                                <CardDescription>
                                    {parcel.itemName ?? 'No item name'}
                                    {parcel.expectedDate !== null &&
                                        ` · expected ${formatDate(parcel.expectedDate)}`}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {parcel.misdelivered && (
                                    <p className="font-medium">
                                        Someone else received this parcel by
                                        mistake. The owner is sorting it out.
                                    </p>
                                )}
                                {parcel.status === 'at_owner' && (
                                    <p>
                                        The owner is keeping this parcel for
                                        you. Pick it up when you are home.
                                    </p>
                                )}
                                <ParcelActions parcel={parcel} />
                            </CardContent>
                        </Card>
                    ))
                )}

                {(canReport || reports.length > 0) && (
                    <section className="space-y-4 border-t pt-6">
                        <div className="flex flex-wrap items-start justify-between gap-4">
                            <Heading
                                variant="small"
                                title="Parcels that are not yours"
                                description="Got a parcel for someone else? Report it here."
                            />
                            {canReport && <ReportDialog />}
                        </div>
                        {reports.map((report) => (
                            <Card key={report.id}>
                                <CardHeader>
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <CardTitle className="text-base">
                                            {report.description}
                                        </CardTitle>
                                        <ParcelReportStatusBadge
                                            status={report.status}
                                            audience="tenant"
                                        />
                                    </div>
                                    {report.reportedAt !== null && (
                                        <CardDescription>
                                            Reported{' '}
                                            {formatDate(report.reportedAt)}
                                        </CardDescription>
                                    )}
                                </CardHeader>
                            </Card>
                        ))}
                    </section>
                )}
            </div>
        </>
    );
}

TenantParcels.layout = {
    breadcrumbs: [
        {
            title: 'Parcels',
            href: index(),
        },
    ],
};
