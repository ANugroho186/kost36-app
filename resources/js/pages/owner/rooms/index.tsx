import { Head } from '@inertiajs/react';
import RoomController from '@/actions/App/Http/Controllers/Owner/RoomController';
import { FormDialog } from '@/components/form-dialog';
import Heading from '@/components/heading';
import { RoomFields } from '@/components/room-fields';
import type { RoomPhoto } from '@/components/room-photos';
import { RoomPhotosDialog } from '@/components/room-photos';
import TextLink from '@/components/text-link';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { edit as editProperty } from '@/routes/owner/property';
import { index } from '@/routes/owner/rooms';

// Sama persis dengan yang dikirim RoomController::index().
type OwnerRoom = {
    id: number;
    number: string;
    capacity: number;
    facilities: string;
    isPublished: boolean;
    isOccupied: boolean;
    occupants: number;
    canDelete: boolean;
    photos: RoomPhoto[];
};

type Props = {
    rooms: OwnerRoom[];
    canAddRooms: boolean;
    maxPhotos: number;
};

function EditRoomDialog({ room }: { room: OwnerRoom }) {
    return (
        <FormDialog
            trigger={
                <Button variant="outline" size="sm">
                    Edit
                </Button>
            }
            title={`Edit room ${room.number}`}
            action={RoomController.update.form(room.id)}
            submitLabel="Save"
        >
            {({ errors }) => (
                <>
                    <RoomFields room={room} errors={errors} />
                    {room.occupants > 0 && (
                        <p className="text-sm text-muted-foreground">
                            {room.occupants === 1
                                ? '1 person lives here right now.'
                                : `${room.occupants} people live here right now.`}
                        </p>
                    )}
                    {!room.canDelete && (
                        <p className="text-sm text-muted-foreground">
                            This room has history (tenancies, complaints,
                            maintenance or inventory), so it cannot be deleted.
                            Untick &quot;Show on the public page&quot; to hide
                            it instead.
                        </p>
                    )}
                </>
            )}
        </FormDialog>
    );
}

export default function RoomsIndex({ rooms, canAddRooms, maxPhotos }: Props) {
    return (
        <>
            <Head title="Rooms" />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Rooms"
                        description="Manage rooms and choose which ones appear on the public page."
                    />
                    {canAddRooms ? (
                        <FormDialog
                            trigger={<Button>Add room</Button>}
                            title="Add room"
                            description="New rooms stay hidden from the public page until you choose to show them."
                            action={RoomController.store.form()}
                            submitLabel="Add room"
                        >
                            {({ errors }) => <RoomFields errors={errors} />}
                        </FormDialog>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            <TextLink href={editProperty()}>
                                Set up the property profile
                            </TextLink>{' '}
                            before adding rooms.
                        </p>
                    )}
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
                                <CardContent className="flex flex-wrap gap-2">
                                    <EditRoomDialog room={room} />
                                    <RoomPhotosDialog
                                        roomId={room.id}
                                        roomNumber={room.number}
                                        photos={room.photos}
                                        maxPhotos={maxPhotos}
                                    />
                                    {room.canDelete && (
                                        <FormDialog
                                            trigger={
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                >
                                                    Delete
                                                </Button>
                                            }
                                            title={`Delete room ${room.number}?`}
                                            description="The room and its photos will be removed. This cannot be undone."
                                            action={RoomController.destroy.form(
                                                room.id,
                                            )}
                                            submitLabel="Delete"
                                            destructive
                                        />
                                    )}
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
