import { Form, Head } from '@inertiajs/react';
import RoomController from '@/actions/App/Http/Controllers/Owner/RoomController';
import Heading from '@/components/heading';
import type { RoomFormValues } from '@/components/room-fields';
import { RoomFields } from '@/components/room-fields';
import type { RoomPhoto } from '@/components/room-photos';
import { RoomPhotos } from '@/components/room-photos';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { index } from '@/routes/owner/rooms';

type Props = {
    room: RoomFormValues & { id: number };
    occupants: number;
    // false kalau kamar punya sewa, keluhan, perawatan, atau inventaris.
    canDelete: boolean;
    photos: RoomPhoto[];
    maxPhotos: number;
};

function DeleteRoomDialog({
    roomId,
    number,
}: {
    roomId: number;
    number: string;
}) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="destructive">Delete room</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete room {number}?</DialogTitle>
                    <DialogDescription>
                        The room and its photos will be removed. This cannot be
                        undone.
                    </DialogDescription>
                </DialogHeader>
                <Form {...RoomController.destroy.form(roomId)}>
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button type="button" variant="secondary">
                                    Cancel
                                </Button>
                            </DialogClose>
                            <Button variant="destructive" disabled={processing}>
                                Delete
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export default function EditRoom({
    room,
    occupants,
    canDelete,
    photos,
    maxPhotos,
}: Props) {
    return (
        <>
            <Head title={`Room ${room.number}`} />

            <div className="max-w-2xl p-4">
                <Heading title={`Room ${room.number}`} />

                <Form
                    {...RoomController.update.form(room.id)}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <RoomFields room={room} errors={errors} />
                            {occupants > 0 && (
                                <p className="text-sm text-muted-foreground">
                                    {occupants === 1
                                        ? '1 person lives here right now.'
                                        : `${occupants} people live here right now.`}
                                </p>
                            )}
                            <Button disabled={processing}>Save</Button>
                        </>
                    )}
                </Form>

                <RoomPhotos
                    roomId={room.id}
                    photos={photos}
                    maxPhotos={maxPhotos}
                />

                <section className="mt-10 space-y-4 border-t pt-6">
                    <Heading
                        variant="small"
                        title="Delete room"
                        description={
                            canDelete
                                ? 'Only rooms without any history can be deleted.'
                                : 'This room has history (tenancies, complaints, maintenance or inventory), so it cannot be deleted. Untick "Show on the public page" to hide it instead.'
                        }
                    />
                    {canDelete && (
                        <DeleteRoomDialog
                            roomId={room.id}
                            number={room.number}
                        />
                    )}
                </section>
            </div>
        </>
    );
}

EditRoom.layout = {
    breadcrumbs: [
        {
            title: 'Rooms',
            href: index(),
        },
    ],
};
