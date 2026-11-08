import { Form, Head } from '@inertiajs/react';
import RentalInquiryController from '@/actions/App/Http/Controllers/Owner/RentalInquiryController';
import { FormDialog } from '@/components/form-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate, formatDateTime } from '@/lib/dates';
import { index } from '@/routes/owner/rental-inquiries';

// Sama dengan App\Enums\RentalInquiryStatus.
type InquiryStatus = 'new' | 'contacted' | 'done';

// Sama persis dengan yang dikirim Owner\RentalInquiryController::index().
type Inquiry = {
    id: number;
    name: string;
    contact: string;
    preferredDate: string | null;
    message: string | null;
    status: InquiryStatus;
    receivedAt: string | null;
    // Tanggal data ini dihapus otomatis, hanya untuk yang sudah selesai.
    deletesOn: string | null;
};

const statusLabels: Record<InquiryStatus, string> = {
    new: 'New',
    contacted: 'Contacted',
    done: 'Done',
};

// Email dibuka di aplikasi email. Nomor HP dibuka di WhatsApp (awalan 0 diganti 62).
// Isian lain ditampilkan apa adanya, tanpa tautan.
function contactHref(contact: string): string | null {
    const trimmed = contact.trim();

    if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmed)) {
        return `mailto:${trimmed}`;
    }

    if (/^\+?[\d\s-]{8,}$/.test(trimmed)) {
        const digits = trimmed.replace(/\D/g, '').replace(/^0/, '62');

        return `https://wa.me/${digits}`;
    }

    return null;
}

// Perubahan status tanpa isian cukup satu klik.
function StatusButton({
    inquiryId,
    status,
    label,
}: {
    inquiryId: number;
    status: InquiryStatus;
    label: string;
}) {
    return (
        <Form
            {...RentalInquiryController.update.form(inquiryId)}
            options={{ preserveScroll: true }}
        >
            {({ processing }) => (
                <>
                    <input type="hidden" name="status" value={status} />
                    <Button size="sm" variant="outline" disabled={processing}>
                        {label}
                    </Button>
                </>
            )}
        </Form>
    );
}

function InquiryCard({ inquiry }: { inquiry: Inquiry }) {
    const href = contactHref(inquiry.contact);

    return (
        <Card className={inquiry.status === 'done' ? 'opacity-70' : ''}>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle>{inquiry.name}</CardTitle>
                    <Badge
                        variant={
                            inquiry.status === 'new' ? 'default' : 'secondary'
                        }
                    >
                        {statusLabels[inquiry.status]}
                    </Badge>
                </div>
                <CardDescription>
                    {/* Tautan ke luar aplikasi (email/WhatsApp), jadi <a> biasa, bukan <Link> Inertia. */}
                    {href !== null ? (
                        <a
                            href={href}
                            target="_blank"
                            rel="noreferrer"
                            className="text-foreground underline underline-offset-4"
                        >
                            {inquiry.contact}
                        </a>
                    ) : (
                        inquiry.contact
                    )}
                    {inquiry.receivedAt !== null &&
                        ` · sent ${formatDateTime(inquiry.receivedAt)}`}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
                {inquiry.preferredDate !== null && (
                    <p>
                        Would like to visit on{' '}
                        <span className="font-medium">
                            {formatDate(inquiry.preferredDate)}
                        </span>
                    </p>
                )}
                {inquiry.message !== null && (
                    <p className="whitespace-pre-line">{inquiry.message}</p>
                )}
                {inquiry.deletesOn !== null && (
                    <p className="text-muted-foreground">
                        Deleted automatically on {formatDate(inquiry.deletesOn)}
                        .
                    </p>
                )}
                <div className="flex flex-wrap gap-2">
                    {inquiry.status === 'new' && (
                        <StatusButton
                            inquiryId={inquiry.id}
                            status="contacted"
                            label="Mark as contacted"
                        />
                    )}
                    {inquiry.status !== 'done' ? (
                        <StatusButton
                            inquiryId={inquiry.id}
                            status="done"
                            label="Mark as done"
                        />
                    ) : (
                        <StatusButton
                            inquiryId={inquiry.id}
                            status="contacted"
                            label="Reopen"
                        />
                    )}
                    <FormDialog
                        trigger={
                            <Button size="sm" variant="ghost">
                                Delete
                            </Button>
                        }
                        title={`Delete the request from ${inquiry.name}?`}
                        description="For spam or requests you no longer need. Their name and contact are removed right away."
                        action={RentalInquiryController.destroy.form(
                            inquiry.id,
                        )}
                        submitLabel="Delete"
                        destructive
                    />
                </div>
            </CardContent>
        </Card>
    );
}

export default function RentalInquiries({
    inquiries,
}: {
    inquiries: Inquiry[];
}) {
    return (
        <>
            <Head title="Viewing requests" />

            <div className="max-w-3xl space-y-6 p-4">
                <Heading
                    title="Viewing requests"
                    description="Sent from the public page by people who want to see a room. Done requests are deleted 30 days later."
                />

                {inquiries.length === 0 ? (
                    <p className="text-muted-foreground">
                        No viewing requests yet.
                    </p>
                ) : (
                    inquiries.map((inquiry) => (
                        <InquiryCard key={inquiry.id} inquiry={inquiry} />
                    ))
                )}
            </div>
        </>
    );
}

RentalInquiries.layout = {
    breadcrumbs: [
        {
            title: 'Viewing requests',
            href: index(),
        },
    ],
};
