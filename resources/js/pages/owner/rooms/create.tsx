import { Form, Head } from '@inertiajs/react';
import RoomController from '@/actions/App/Http/Controllers/Owner/RoomController';
import Heading from '@/components/heading';
import { RoomFields } from '@/components/room-fields';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/owner/rooms';

export default function CreateRoom() {
    return (
        <>
            <Head title="Add room" />

            <div className="max-w-2xl p-4">
                <Heading
                    title="Add room"
                    description="New rooms stay hidden from the public page until you choose to show them."
                />

                <Form {...RoomController.store.form()} className="space-y-6">
                    {({ processing, errors }) => (
                        <>
                            <RoomFields errors={errors} />
                            <Button disabled={processing}>Add room</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateRoom.layout = {
    breadcrumbs: [
        {
            title: 'Rooms',
            href: index(),
        },
        {
            title: 'Add room',
            href: create(),
        },
    ],
};
