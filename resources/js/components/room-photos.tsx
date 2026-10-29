import { Form } from '@inertiajs/react';
import RoomPhotoController from '@/actions/App/Http/Controllers/Owner/RoomPhotoController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { firstErrorFor } from '@/lib/form-errors';

export type RoomPhoto = {
    id: number;
    url: string;
};

type RoomPhotosProps = {
    roomId: number;
    roomNumber: string;
    photos: RoomPhoto[];
    maxPhotos: number;
};

/**
 * Modal foto kamar. Tidak tertutup setelah unggah, jadikan sampul, atau hapus, supaya hasilnya
 * langsung terlihat. Daftar foto ikut diperbarui karena halaman menerima props baru dari server.
 */
export function RoomPhotosDialog(props: RoomPhotosProps) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline" size="sm">
                    Photos ({props.photos.length})
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90dvh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Photos of room {props.roomNumber}</DialogTitle>
                    <DialogDescription>
                        The first photo is shown on the public page. Up to{' '}
                        {props.maxPhotos} photos, JPG, PNG or WebP, 5 MB each.
                    </DialogDescription>
                </DialogHeader>
                <RoomPhotos {...props} />
            </DialogContent>
        </Dialog>
    );
}

function RoomPhotos({ roomId, photos, maxPhotos }: RoomPhotosProps) {
    return (
        <div className="space-y-4">
            {photos.length > 0 && (
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                    {photos.map((photo, index) => (
                        <div key={photo.id} className="space-y-2">
                            <img
                                src={photo.url}
                                alt={`Photo ${index + 1}`}
                                className="aspect-video w-full rounded-md border object-cover"
                            />
                            <div className="flex flex-wrap items-center gap-2">
                                {index === 0 ? (
                                    <Badge>Cover</Badge>
                                ) : (
                                    <Form
                                        {...RoomPhotoController.cover.form({
                                            room: roomId,
                                            photo: photo.id,
                                        })}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                disabled={processing}
                                            >
                                                Make cover
                                            </Button>
                                        )}
                                    </Form>
                                )}
                                <Form
                                    {...RoomPhotoController.destroy.form({
                                        room: roomId,
                                        photo: photo.id,
                                    })}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button
                                            size="sm"
                                            variant="ghost"
                                            disabled={processing}
                                        >
                                            Delete
                                        </Button>
                                    )}
                                </Form>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {photos.length < maxPhotos && (
                <Form
                    {...RoomPhotoController.store.form(roomId)}
                    resetOnSuccess
                    options={{ preserveScroll: true }}
                    className="space-y-3"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="photos">Add photos</Label>
                                <Input
                                    id="photos"
                                    name="photos[]"
                                    type="file"
                                    multiple
                                    required
                                    accept="image/jpeg,image/png,image/webp"
                                />
                                <InputError
                                    message={firstErrorFor(errors, 'photos')}
                                />
                            </div>
                            <Button disabled={processing}>
                                {processing && <Spinner />}
                                Upload
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </div>
    );
}
