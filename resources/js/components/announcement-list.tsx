import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDateTime } from '@/lib/dates';

// Sama persis dengan AnnouncementController::liveFor(): hanya pengumuman yang sedang tayang.
export type TenantAnnouncement = {
    id: number;
    title: string;
    body: string;
    // Format YYYY-MM-DDTHH:mm, jam dinding WIB.
    publishedAt: string;
};

export function AnnouncementList({
    announcements,
}: {
    announcements: TenantAnnouncement[];
}) {
    return (
        <div className="space-y-4">
            {announcements.map((announcement) => (
                <Card key={announcement.id}>
                    <CardHeader>
                        <CardTitle>{announcement.title}</CardTitle>
                        <CardDescription>
                            {formatDateTime(announcement.publishedAt)}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="text-sm">
                        <p className="whitespace-pre-line">
                            {announcement.body}
                        </p>
                    </CardContent>
                </Card>
            ))}
        </div>
    );
}
