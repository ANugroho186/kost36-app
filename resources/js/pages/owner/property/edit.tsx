import { Form, Head } from '@inertiajs/react';
import PropertyController from '@/actions/App/Http/Controllers/Owner/PropertyController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { edit } from '@/routes/owner/property';

type Props = {
    // null kalau profil kost belum pernah diisi.
    property: {
        name: string;
        address: string;
        description: string;
        rules: string;
    } | null;
};

export default function PropertyEdit({ property }: Props) {
    return (
        <>
            <Head title="Property profile" />

            <div className="max-w-2xl p-4">
                <Heading
                    title="Property profile"
                    description="Everything here is shown on the public page."
                />

                <Form
                    {...PropertyController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    maxLength={100}
                                    defaultValue={property?.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="address">Address</Label>
                                <Textarea
                                    id="address"
                                    name="address"
                                    required
                                    rows={2}
                                    maxLength={500}
                                    defaultValue={property?.address}
                                />
                                <InputError message={errors.address} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    required
                                    rows={4}
                                    maxLength={2000}
                                    defaultValue={property?.description}
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="rules">House rules</Label>
                                <Textarea
                                    id="rules"
                                    name="rules"
                                    required
                                    rows={4}
                                    maxLength={2000}
                                    defaultValue={property?.rules}
                                />
                                <InputError message={errors.rules} />
                            </div>

                            <Button disabled={processing}>Save</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

PropertyEdit.layout = {
    breadcrumbs: [
        {
            title: 'Property profile',
            href: edit(),
        },
    ],
};
