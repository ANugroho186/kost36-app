import { Head, Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { BedDouble, Inbox, Package, Receipt, Wrench } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as complaints } from '@/routes/owner/complaints';
import type { RouteDefinition } from '@/wayfinder';

// Sama persis dengan yang dikirim DashboardController::owner(): hanya angka.
type Props = {
    rooms: { total: number; occupied: number };
    pendingPaymentProofs: number;
    openComplaints: number;
    parcels: { waiting: number; atOwner: number };
    newRentalInquiries: number;
};

function StatCard({
    icon: Icon,
    title,
    value,
    description,
    href,
}: {
    icon: LucideIcon;
    title: string;
    value: string | number;
    description?: string;
    // Halaman tempat angka ini bisa ditindaklanjuti. Kosong kalau fiturnya belum ada.
    href?: RouteDefinition<'get'>;
}) {
    const card = (
        <Card className={href && 'h-full transition-colors hover:bg-accent'}>
            <CardHeader>
                <CardDescription className="flex items-center gap-2">
                    <Icon className="size-4" />
                    {title}
                </CardDescription>
                <CardTitle className="text-3xl tabular-nums">{value}</CardTitle>
            </CardHeader>
            {description && (
                <CardContent className="text-sm text-muted-foreground">
                    {description}
                </CardContent>
            )}
        </Card>
    );

    return href ? <Link href={href}>{card}</Link> : card;
}

export default function OwnerDashboard({
    rooms,
    pendingPaymentProofs,
    openComplaints,
    parcels,
    newRentalInquiries,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-3">
                <StatCard
                    icon={BedDouble}
                    title="Occupied rooms"
                    value={`${rooms.occupied} / ${rooms.total}`}
                    description={`${rooms.total - rooms.occupied} available`}
                />
                <StatCard
                    icon={Receipt}
                    title="Payment proofs to review"
                    value={pendingPaymentProofs}
                />
                <StatCard
                    icon={Wrench}
                    title="Open complaints"
                    value={openComplaints}
                    href={complaints()}
                />
                <StatCard
                    icon={Package}
                    title="Parcels at your place"
                    value={parcels.atOwner}
                    description={`${parcels.waiting} more on the way`}
                />
                <StatCard
                    icon={Inbox}
                    title="New viewing requests"
                    value={newRentalInquiries}
                />
            </div>
        </>
    );
}

OwnerDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
