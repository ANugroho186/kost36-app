import { Head } from '@inertiajs/react';
import AnnouncementController from '@/actions/App/Http/Controllers/Owner/AnnouncementController';
import type { AnnouncementFormValues } from '@/components/announcement-fields';
import { AnnouncementFields } from '@/components/announcement-fields';
import { FormDialog } from '@/components/form-dialog';
import Heading from '@/components/heading';
import TextLink from '@/components/text-link';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDateTime } from '@/lib/dates';
import { index } from '@/routes/owner/announcements';
import { edit as editProperty } from '@/routes/owner/property';

// Sama dengan App\Enums\AnnouncementStatus.
type AnnouncementStatus = 'scheduled' | 'live' | 'expired';

// Sama persis dengan yang dikirim Owner\AnnouncementController::index().
type OwnerAnnouncement = AnnouncementFormValues & {
    id: number;
    status: AnnouncementStatus;
};

type Props = {
    announcements: OwnerAnnouncement[];
    canCreate: boolean;
    now: string;
};

const statusLabels: Record<AnnouncementStatus, string> = {
    scheduled: 'Scheduled',
    live: 'Live',
    expired: 'Expired',
};

function AnnouncementCard({
    announcement,
    now,
}: {
    announcement: OwnerAnnouncement;
    now: string;
}) {
    return (
        <Card className={announcement.status === 'expired' ? 'opacity-70' : ''}>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <CardTitle>{announcement.title}</CardTitle>
                    <Badge
                        variant={
                            announcement.status === 'live'
                                ? 'default'
                                : 'outline'
                        }
                    >
                        {statusLabels[announcement.status]}
                    </Badge>
                </div>
                <CardDescription>
                    Shown from {formatDateTime(announcement.publishedAt)}
                    {announcement.expiresAt !== null &&
                        ` until ${formatDateTime(announcement.expiresAt)}`}
                </CardDescription>
            </CardHeader>
            <CardContent className="space-y-4 text-sm">
                <p className="whitespace-pre-line">{announcement.body}</p>
                <div className="flex flex-wrap gap-2">
                    <FormDialog
                        trigger={
                            <Button variant="outline" size="sm">
                                Edit
                            </Button>
                        }
                        title="Edit announcement"
                        action={AnnouncementController.update.form(
                            announcement.id,
                        )}
                        submitLabel="Save"
                    >
                        {({ errors }) => (
                            <AnnouncementFields
                                announcement={announcement}
                                now={now}
                                errors={errors}
                            />
                        )}
                    </FormDialog>
                    <FormDialog
                        trigger={
                            <Button variant="ghost" size="sm">
                                Delete
                            </Button>
                        }
                        title={`Delete “${announcement.title}”?`}
                        description="Tenants will no longer see it. This cannot be undone."
                        action={AnnouncementController.destroy.form(
                            announcement.id,
                        )}
                        submitLabel="Delete"
                        destructive
                    />
                </div>
            </CardContent>
        </Card>
    );
}

export default function OwnerAnnouncements({
    announcements,
    canCreate,
    now,
}: Props) {
    return (
        <>
            <Head title="Announcements" />

            <div className="max-w-3xl space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Announcements"
                        description="Messages for every tenant, for example planned water or power cuts."
                    />
                    {canCreate ? (
                        <FormDialog
                            trigger={<Button>New announcement</Button>}
                            title="New announcement"
                            action={AnnouncementController.store.form()}
                            submitLabel="Save announcement"
                        >
                            {({ errors }) => (
                                <AnnouncementFields now={now} errors={errors} />
                            )}
                        </FormDialog>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            <TextLink href={editProperty()}>
                                Set up the property profile
                            </TextLink>{' '}
                            before writing announcements.
                        </p>
                    )}
                </div>

                {announcements.length === 0 ? (
                    <p className="text-muted-foreground">
                        No announcements yet.
                    </p>
                ) : (
                    announcements.map((announcement) => (
                        <AnnouncementCard
                            key={announcement.id}
                            announcement={announcement}
                            now={now}
                        />
                    ))
                )}
            </div>
        </>
    );
}

OwnerAnnouncements.layout = {
    breadcrumbs: [
        {
            title: 'Announcements',
            href: index(),
        },
    ],
};
