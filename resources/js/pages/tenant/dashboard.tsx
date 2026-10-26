import { Head } from '@inertiajs/react';
import { CalendarClock, TriangleAlert, Users } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';

// Sama persis dengan yang dikirim DashboardController::tenant().
type TenantTenancy = {
    roomNumber: string;
    status: 'active' | 'inactive';
    paidUntil: string;
    vacateBy: string | null;
    roommates: string[];
};

type Props = {
    tenancy: TenantTenancy | null;
};

// Tanggal dari server berformat YYYY-MM-DD. Ditambah jam 00:00 supaya dibaca sebagai
// waktu lokal dan tanggalnya tidak bergeser karena zona waktu.
function formatDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString('en-GB', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export default function TenantDashboard({ tenancy }: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="max-w-2xl space-y-4 p-4">
                {tenancy === null ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>No active room</CardTitle>
                            <CardDescription>
                                Your account is not linked to a room right now.
                                If this looks wrong, please contact the property
                                owner.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    <>
                        {tenancy.status === 'inactive' &&
                            tenancy.vacateBy !== null && (
                                <Alert variant="destructive">
                                    <TriangleAlert />
                                    <AlertTitle>
                                        Your tenancy has been deactivated
                                    </AlertTitle>
                                    <AlertDescription>
                                        Please vacate the room by{' '}
                                        {formatDate(tenancy.vacateBy)}. An
                                        approved payment before then reactivates
                                        it.
                                    </AlertDescription>
                                </Alert>
                            )}

                        <Card>
                            <CardHeader>
                                <CardTitle>Room {tenancy.roomNumber}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <p className="flex items-center gap-2">
                                    <CalendarClock className="size-4 text-muted-foreground" />
                                    <span className="text-muted-foreground">
                                        Next payment due:
                                    </span>
                                    <span className="font-medium">
                                        {formatDate(tenancy.paidUntil)}
                                    </span>
                                </p>
                                <p className="flex items-center gap-2">
                                    <Users className="size-4 text-muted-foreground" />
                                    <span className="text-muted-foreground">
                                        Roommate:
                                    </span>
                                    <span className="font-medium">
                                        {tenancy.roommates.length > 0
                                            ? tenancy.roommates.join(', ')
                                            : 'None'}
                                    </span>
                                </p>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </>
    );
}

TenantDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
