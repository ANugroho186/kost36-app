import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { firstErrorFor } from '@/lib/form-errors';

// Kolom form keluhan untuk penghuni dan pemilik. Nama field mengikuti StoreComplaintRequest di server.
export function ComplaintFields({
    errors,
    maxPhotos,
    photoNote,
}: {
    errors: Partial<Record<string, string>>;
    maxPhotos: number;
    // Siapa saja yang bisa membuka foto, dijelaskan dari sudut pandang pengisi form.
    photoNote: string;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="title">What is the problem?</Label>
                <Input
                    id="title"
                    name="title"
                    required
                    maxLength={100}
                    placeholder="Leaking tap"
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">Details</Label>
                <Textarea
                    id="description"
                    name="description"
                    required
                    rows={4}
                    maxLength={2000}
                    placeholder="Where it is, since when, and how bad it is"
                />
                <InputError message={errors.description} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="photos">Photos (optional)</Label>
                <Input
                    id="photos"
                    name="photos[]"
                    type="file"
                    multiple
                    accept="image/jpeg,image/png,image/webp"
                />
                <p className="text-sm text-muted-foreground">
                    Up to {maxPhotos} photos, JPG, PNG or WebP, 5 MB each.{' '}
                    {photoNote} Location data is removed.
                </p>
                <InputError message={firstErrorFor(errors, 'photos')} />
            </div>
        </>
    );
}
