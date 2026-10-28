import { Form, Head } from '@inertiajs/react';
import TenancyController from '@/actions/App/Http/Controllers/Owner/TenancyController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { create, index } from '@/routes/owner/tenancies';

type Props = {
    // Hanya kamar yang sedang kosong.
    rooms: { id: number; number: string }[];
    selectedRoomId: number | null;
    today: string;
};

export default function CreateTenancy({ rooms, selectedRoomId, today }: Props) {
    return (
        <>
            <Head title="Start a tenancy" />

            <div className="max-w-2xl p-4">
                <Heading
                    title="Start a tenancy"
                    description="The next payment date is calculated from the start date and the months paid upfront."
                />

                {rooms.length === 0 ? (
                    <p className="text-muted-foreground">
                        All rooms are occupied.
                    </p>
                ) : (
                    <Form
                        {...TenancyController.store.form()}
                        className="space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="room_id">Room</Label>
                                    <Select
                                        name="room_id"
                                        required
                                        defaultValue={
                                            selectedRoomId
                                                ? String(selectedRoomId)
                                                : undefined
                                        }
                                    >
                                        <SelectTrigger
                                            id="room_id"
                                            className="w-full"
                                        >
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
                                    <Label htmlFor="start_date">
                                        Start date
                                    </Label>
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
                                    <Label htmlFor="months_paid">
                                        Months paid upfront
                                    </Label>
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

                                <Button disabled={processing}>
                                    Start tenancy
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}

CreateTenancy.layout = {
    breadcrumbs: [
        {
            title: 'Tenancies',
            href: index(),
        },
        {
            title: 'Start a tenancy',
            href: create(),
        },
    ],
};
