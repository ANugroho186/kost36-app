import { Form, Head } from '@inertiajs/react';
import ComplaintController from '@/actions/App/Http/Controllers/ComplaintController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { firstErrorFor } from '@/lib/form-errors';
import { create, index } from '@/routes/complaints';

type Props = {
    roomNumber: string;
    maxPhotos: number;
};

export default function CreateComplaint({ roomNumber, maxPhotos }: Props) {
    return (
        <>
            <Head title="Report a problem" />

            <div className="max-w-2xl p-4">
                <Heading
                    title="Report a problem"
                    description={`For room ${roomNumber}. The owner and your roommate can see this complaint.`}
                />

                <Form
                    {...ComplaintController.store.form()}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">
                                    What is the problem?
                                </Label>
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
                                <Label htmlFor="photos">
                                    Photos (optional)
                                </Label>
                                <Input
                                    id="photos"
                                    name="photos[]"
                                    type="file"
                                    multiple
                                    accept="image/jpeg,image/png,image/webp"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Up to {maxPhotos} photos, JPG, PNG or WebP,
                                    5 MB each. Only the owner and your roommate
                                    can open them. Location data is removed.
                                </p>
                                <InputError
                                    message={firstErrorFor(errors, 'photos')}
                                />
                            </div>

                            <Button disabled={processing}>
                                {processing && <Spinner />}
                                Send complaint
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

CreateComplaint.layout = {
    breadcrumbs: [
        {
            title: 'Complaints',
            href: index(),
        },
        {
            title: 'Report a problem',
            href: create(),
        },
    ],
};
