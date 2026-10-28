import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export type RoomFormValues = {
    number: string;
    capacity: number;
    facilities: string;
    isPublished: boolean;
};

// Kolom form kamar untuk halaman tambah dan ubah. Nama field mengikuti RoomRequest di server.
export function RoomFields({
    room,
    errors,
}: {
    room?: RoomFormValues;
    errors: Partial<Record<string, string>>;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="number">Room number</Label>
                <Input
                    id="number"
                    name="number"
                    required
                    maxLength={20}
                    defaultValue={room?.number}
                />
                <InputError message={errors.number} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="capacity">Capacity (people)</Label>
                <Input
                    id="capacity"
                    name="capacity"
                    type="number"
                    required
                    min={1}
                    max={2}
                    defaultValue={room?.capacity ?? 2}
                />
                <InputError message={errors.capacity} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="facilities">Facilities</Label>
                <Textarea
                    id="facilities"
                    name="facilities"
                    required
                    rows={3}
                    maxLength={1000}
                    defaultValue={room?.facilities}
                    placeholder="Bed, wardrobe, desk, fan"
                />
                <InputError message={errors.facilities} />
            </div>

            <div className="grid gap-2">
                <div className="flex items-center gap-3">
                    <Checkbox
                        id="is_published"
                        name="is_published"
                        value="1"
                        defaultChecked={room?.isPublished ?? false}
                    />
                    <Label htmlFor="is_published">
                        Show on the public page
                    </Label>
                </div>
                <InputError message={errors.is_published} />
            </div>
        </>
    );
}
