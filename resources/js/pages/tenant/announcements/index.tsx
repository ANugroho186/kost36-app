import { Head } from '@inertiajs/react';
import type { TenantAnnouncement } from '@/components/announcement-list';
import { AnnouncementList } from '@/components/announcement-list';
import Heading from '@/components/heading';
import { index } from '@/routes/announcements';

export default function TenantAnnouncements({
    announcements,
}: {
    announcements: TenantAnnouncement[];
}) {
    return (
        <>
            <Head title="Announcements" />

            <div className="max-w-2xl space-y-6 p-4">
                <Heading
                    title="Announcements"
                    description="News from the property owner, newest first."
                />

                {announcements.length === 0 ? (
                    <p className="text-muted-foreground">
                        No announcements right now.
                    </p>
                ) : (
                    <AnnouncementList announcements={announcements} />
                )}
            </div>
        </>
    );
}

TenantAnnouncements.layout = {
    breadcrumbs: [
        {
            title: 'Announcements',
            href: index(),
        },
    ],
};
