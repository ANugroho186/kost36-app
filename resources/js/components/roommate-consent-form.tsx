import { Form } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { acceptsRoommate } from '@/routes/tenancies';

// Tombol "bersedia berbagi kamar" untuk dashboard penghuni dan halaman sewa pemilik.
// Siapa yang boleh menekannya tetap dicek server (TenancyPolicy::toggleRoommate).
export function RoommateConsentForm({
    tenancyId,
    accepts,
}: {
    tenancyId: number;
    accepts: boolean;
}) {
    return (
        <Form
            {...acceptsRoommate.form(tenancyId)}
            options={{ preserveScroll: true }}
            className="space-y-2"
        >
            {({ processing, errors }) => (
                <>
                    <input
                        type="hidden"
                        name="accepts_roommate"
                        value={accepts ? '0' : '1'}
                    />
                    <Button
                        variant={accepts ? 'outline' : 'default'}
                        disabled={processing}
                    >
                        {accepts
                            ? 'Mark the room as full'
                            : 'Open the room for someone I know'}
                    </Button>
                    <InputError message={errors.accepts_roommate} />
                </>
            )}
        </Form>
    );
}
