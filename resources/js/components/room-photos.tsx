import { Form } from '@inertiajs/react';
import RoomPhotoController from '@/actions/App/Http/Controllers/Owner/RoomPhotoController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { firstErrorFor } from '@/lib/form-errors';

export type RoomPhoto = {
    id: number;
    url: string;
};

export function RoomPhotos({
    roomId,
    photos,
    maxPhotos,
}: {
    roomId: number;
    photos: RoomPhoto[];
    maxPhotos: number;
}) {
    return (
        <section className="mt-10 space-y-4 border-t pt-6">
            <Heading
                variant="small"
                title="Photos"
                description={`The first photo is shown on the public page. Up to ${maxPhotos} photos, JPG, PNG or WebP, 5 MB each.`}
            />

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
        </section>
    );
}
