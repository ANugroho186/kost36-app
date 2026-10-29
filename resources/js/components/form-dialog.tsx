import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { useState } from 'react';
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
import { Spinner } from '@/components/ui/spinner';
import type { RouteFormDefinition } from '@/wayfinder';

export type FormDialogFields = {
    errors: Partial<Record<string, string>>;
    processing: boolean;
};

type Props = {
    // Tombol pembuka. Diteruskan ke DialogTrigger asChild, jadi harus satu elemen (misalnya <Button>).
    trigger: ReactNode;
    title: string;
    description?: ReactNode;
    // Hasil `.form()` dari Wayfinder, misalnya RoomController.store.form(). PUT/DELETE juga
    // bertipe 'post', karena Wayfinder mengirimnya sebagai POST dengan _method.
    action: RouteFormDefinition<'post'>;
    submitLabel: string;
    destructive?: boolean;
    // Untuk field password: dikosongkan lagi kalau salah.
    resetOnError?: boolean;
    children?: (fields: FormDialogFields) => ReactNode;
};

/**
 * Modal berisi satu form Inertia.
 *
 * - Kalau berhasil, modal ditutup. Isi modal di-unmount, jadi form kosong lagi saat dibuka ulang.
 * - Kalau validasi gagal, modal tetap terbuka dan menampilkan error. Inertia mengirim form dengan
 *   preserveState: true, jadi state "terbuka" di sini tidak hilang saat halaman menerima error.
 */
export function FormDialog({
    trigger,
    title,
    description,
    action,
    submitLabel,
    destructive = false,
    resetOnError = false,
    children,
}: Props) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent
                className="max-h-[90dvh] overflow-y-auto"
                // Radix memberi peringatan kalau tidak ada deskripsi, kecuali dinyatakan kosong begini.
                {...(description === undefined && {
                    'aria-describedby': undefined,
                })}
            >
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description !== undefined && (
                        <DialogDescription>{description}</DialogDescription>
                    )}
                </DialogHeader>
                <Form
                    {...action}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnError={resetOnError}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            {children?.({ processing, errors })}
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    variant={
                                        destructive ? 'destructive' : 'default'
                                    }
                                    disabled={processing}
                                >
                                    {processing && <Spinner />}
                                    {submitLabel}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
