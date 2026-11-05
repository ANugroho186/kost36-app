import { Form, Head } from '@inertiajs/react';
import ParcelController from '@/actions/App/Http/Controllers/Owner/ParcelController';
import Heading from '@/components/heading';
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

export default function OwnerParcels({ parcels }: { parcels: OwnerParcel[] }) {
    return (
        <>
            <Head title="Parcels" />

            <div className="max-w-3xl space-y-6 p-4">
                <Heading
                    title="Parcels"
                    description="Parcels your tenants are expecting. What is inside stays private to each tenant."
                />

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
