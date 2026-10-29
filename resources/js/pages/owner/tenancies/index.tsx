import { Head, Link } from '@inertiajs/react';
import TenancyController from '@/actions/App/Http/Controllers/Owner/TenancyController';
import { FormDialog } from '@/components/form-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { formatDate } from '@/lib/dates';
import { index, show } from '@/routes/owner/tenancies';

// Sama persis dengan yang dikirim TenancyController::index().
type OwnerTenancy = {
    id: number;
    roomNumber: string;
    status: 'active' | 'inactive';
    paidUntil: string;
    occupants: string[];
    acceptsRoommate: boolean;
};

type Room = { id: number; number: string };

type Props = {
    tenancies: OwnerTenancy[];
    // Hanya kamar yang sedang kosong.
    availableRooms: Room[];
    today: string;
};

function StartTenancyDialog({
    rooms,
    today,
}: {
    rooms: Room[];
    today: string;
}) {
    return (
        <FormDialog
            trigger={<Button>Start a tenancy</Button>}
            title="Start a tenancy"
            description="The next payment date is calculated from the start date and the months paid upfront."
            action={TenancyController.store.form()}
            submitLabel="Start tenancy"
        >
            {({ errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="room_id">Room</Label>
                        <Select name="room_id" required>
                            <SelectTrigger id="room_id" className="w-full">
                                <SelectValue placeholder="Choose an empty room" />
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

                    <div className="grid gap-2">
                        <Label htmlFor="start_date">Start date</Label>
                        <Input
                            id="start_date"
                            name="start_date"
                            type="date"
                            required
                            defaultValue={today}
                        />
                        <InputError message={errors.start_date} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="months_paid">Months paid upfront</Label>
                        <Input
                            id="months_paid"
                            name="months_paid"
                            type="number"
                            required
                            min={1}
                            max={12}
                            defaultValue={1}
                        />
                        <InputError message={errors.months_paid} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="nickname">
                            First occupant&apos;s nickname
                        </Label>
                        <Input
                            id="nickname"
                            name="nickname"
                            required
                            maxLength={50}
                        />
                        <p className="text-sm text-muted-foreground">
                            A login account can be linked later.
                        </p>
                        <InputError message={errors.nickname} />
                    </div>
                </>
            )}
        </FormDialog>
    );
}

export default function TenanciesIndex({
    tenancies,
    availableRooms,
    today,
}: Props) {
    return (
        <>
            <Head title="Tenancies" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Tenancies"
                        description="Rooms that are currently lived in."
                    />
                    {availableRooms.length > 0 ? (
                        <StartTenancyDialog
                            rooms={availableRooms}
                            today={today}
                        />
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
