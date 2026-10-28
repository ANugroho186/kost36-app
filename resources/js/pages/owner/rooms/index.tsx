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
import { create, edit, index } from '@/routes/owner/rooms';

// Sama persis dengan yang dikirim RoomController::index().
type OwnerRoom = {
    id: number;
    number: string;
    capacity: number;
    isPublished: boolean;
    isOccupied: boolean;
    occupants: number;
};

export default function RoomsIndex({ rooms }: { rooms: OwnerRoom[] }) {
    return (
        <>
            <Head title="Rooms" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Rooms"
                        description="Manage rooms and choose which ones appear on the public page."
                    />
                    <Button asChild>
                        <Link href={create()}>Add room</Link>
                    </Button>
                </div>

                {rooms.length === 0 ? (
                    <p className="text-muted-foreground">No rooms yet.</p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {rooms.map((room) => (
                            <Card key={room.id}>
                                <CardHeader>
                                    <div className="flex items-center justify-between gap-2">
                                        <CardTitle>
                                            Room {room.number}
                                        </CardTitle>
                                        <Badge
                                            variant={
                                                room.isOccupied
                                                    ? 'secondary'
                                                    : 'default'
                                            }
                                        >
                                            {room.isOccupied
                                                ? 'Occupied'
                                                : 'Available'}
                                        </Badge>
                                    </div>
                                    <CardDescription>
                                        {room.occupants} / {room.capacity}{' '}
                                        people ·{' '}
                                        {room.isPublished
                                            ? 'Shown publicly'
                                            : 'Hidden from the public page'}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={edit(room.id)}>Edit</Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

RoomsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Rooms',
            href: index(),
        },
    ],
};
