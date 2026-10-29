import { Head } from '@inertiajs/react';
import { CalendarClock, KeyRound, TriangleAlert, Users } from 'lucide-react';
import type { TenantAnnouncement } from '@/components/announcement-list';
import { AnnouncementList } from '@/components/announcement-list';
import Heading from '@/components/heading';
import { RoommateConsentForm } from '@/components/roommate-consent-form';
import TextLink from '@/components/text-link';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { formatDate } from '@/lib/dates';
import { dashboard } from '@/routes';
import { index as announcementsIndex } from '@/routes/announcements';
import { edit as editSecurity } from '@/routes/security';

// Sama persis dengan yang dikirim DashboardController::tenant().
type TenantTenancy = {
    id: number;
    roomNumber: string;
    status: 'active' | 'inactive';
    paidUntil: string;
    vacateBy: string | null;
    roommates: string[];
    acceptsRoommate: boolean;
    canToggleRoommate: boolean;
};

type Props = {
    tenancy: TenantTenancy | null;
    hasTemporaryPassword: boolean;
    // 3 pengumuman terbaru yang sedang tayang. Semuanya ada di halaman Announcements.
    announcements: TenantAnnouncement[];
};

export default function TenantDashboard({
    tenancy,
    hasTemporaryPassword,
    announcements,
}: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="max-w-2xl space-y-4 p-4">
                {/* Pengingat saja, tidak wajib: hilang setelah password diganti sendiri. */}
                {hasTemporaryPassword && (
                    <Alert>
                        <KeyRound />
                        <AlertTitle>
                            You are using a temporary password
                        </AlertTitle>
                        <AlertDescription>
                            <p>
                                It was created by the property owner. We
                                recommend choosing your own password in{' '}
                                <TextLink href={editSecurity()}>
                                    Settings → Security
                                </TextLink>
                                .
                            </p>
                        </AlertDescription>
                    </Alert>
                )}

                {tenancy === null ? (
                    <Card>
                        <CardHeader>
                            <CardTitle>No active room</CardTitle>
                            <CardDescription>
                                Your account is not linked to a room right now.
                                If this looks wrong, please contact the property
                                owner.
                            </CardDescription>
                        </CardHeader>
                    </Card>
                ) : (
                    <>
                        {tenancy.status === 'inactive' &&
                            tenancy.vacateBy !== null && (
                                <Alert variant="destructive">
                                    <TriangleAlert />
                                    <AlertTitle>
                                        Your tenancy has been deactivated
                                    </AlertTitle>
                                    <AlertDescription>
                                        Please vacate the room by{' '}
                                        {formatDate(tenancy.vacateBy)}. An
                                        approved payment before then reactivates
                                        it.
                                    </AlertDescription>
                                </Alert>
                            )}

                        <Card>
                            <CardHeader>
                                <CardTitle>Room {tenancy.roomNumber}</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <p className="flex items-center gap-2">
                                    <CalendarClock className="size-4 text-muted-foreground" />
                                    <span className="text-muted-foreground">
                                        Next payment due:
                                    </span>
                                    <span className="font-medium">
                                        {formatDate(tenancy.paidUntil)}
                                    </span>
                                </p>
                                <p className="flex items-center gap-2">
                                    <Users className="size-4 text-muted-foreground" />
                                    <span className="text-muted-foreground">
                                        Roommate:
                                    </span>
                                    <span className="font-medium">
                                        {tenancy.roommates.length > 0
                                            ? tenancy.roommates.join(', ')
                                            : 'None'}
                                    </span>
                                </p>
                            </CardContent>
                        </Card>

                        {tenancy.canToggleRoommate && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Share your room</CardTitle>
                                    <CardDescription>
                                        {tenancy.acceptsRoommate
                                            ? 'Your room is open for one more person you know. The owner can now add them.'
                                            : 'If a friend from work or campus wants to share this room with you, open it so the owner can add them. Your room is never offered to strangers.'}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <RoommateConsentForm
                                        tenancyId={tenancy.id}
                                        accepts={tenancy.acceptsRoommate}
                                    />
                                </CardContent>
                            </Card>
                        )}

                        {announcements.length > 0 && (
                            <section className="space-y-3 pt-2">
                                <div className="flex flex-wrap items-baseline justify-between gap-2">
                                    <Heading
                                        variant="small"
                                        title="Latest announcements"
                                    />
                                    <TextLink href={announcementsIndex()}>
                                        See all
                                    </TextLink>
                                </div>
                                <AnnouncementList
                                    announcements={announcements}
                                />
                            </section>
                        )}
                    </>
                )}
            </div>
        </>
    );
}

TenantDashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
