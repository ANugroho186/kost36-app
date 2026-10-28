import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate } from '@/lib/dates';
import { create, index, show } from '@/routes/owner/tenancies';

// Sama persis dengan yang dikirim TenancyController::index().
type OwnerTenancy = {
    id: number;
    roomNumber: string;
    status: 'active' | 'inactive';
    paidUntil: string;
    occupants: string[];
    acceptsRoommate: boolean;
};

type Props = {
    tenancies: OwnerTenancy[];
    availableRooms: number;
};

export default function TenanciesIndex({ tenancies, availableRooms }: Props) {
    return (
        <>
            <Head title="Tenancies" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Tenancies"
                        description="Rooms that are currently lived in."
                    />
                    {availableRooms > 0 ? (
                        <Button asChild>
                            <Link href={create()}>Start a tenancy</Link>
                        </Button>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            All rooms are occupied.
                        </p>
                    )}
                </div>

                {tenancies.length === 0 ? (
                    <p className="text-muted-foreground">
                        No room is occupied right now.
                    </p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {tenancies.map((tenancy) => (
                            <Card key={tenancy.id}>
                                <CardHeader>
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                        <CardTitle>
                                            Room {tenancy.roomNumber}
                                        </CardTitle>
                                        <Badge
                                            variant={
                                                tenancy.status === 'active'
                                                    ? 'secondary'
                                                    : 'destructive'
                                            }
                                        >
                                            {tenancy.status === 'active'
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </div>
                                    <CardDescription>
                                        Next payment due{' '}
                                        {formatDate(tenancy.paidUntil)}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-3 text-sm">
                                    <p>
                                        {tenancy.occupants.length > 0
                                            ? tenancy.occupants.join(', ')
                                            : 'No named occupants'}
                                    </p>
                                    {tenancy.acceptsRoommate && (
                                        <Badge variant="outline">
                                            Open for a roommate
                                        </Badge>
                                    )}
                                    <div>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={show(tenancy.id)}>
                                                Details
                                            </Link>
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

TenanciesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Tenancies',
            href: index(),
        },
    ],
};
