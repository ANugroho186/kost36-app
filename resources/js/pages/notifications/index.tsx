import { Form, Head } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import NotificationController from '@/actions/App/Http/Controllers/NotificationController';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { formatDateTime } from '@/lib/dates';
import { cn } from '@/lib/utils';
import { index } from '@/routes/notifications';

// Sama persis dengan yang dikirim NotificationController::index(): 50 terbaru.
type PortalNotification = {
    id: string;
    message: string;
    createdAt: string | null;
    read: boolean;
};

export default function Notifications({
    notifications,
}: {
    notifications: PortalNotification[];
}) {
    const hasUnread = notifications.some((notification) => !notification.read);

    return (
        <>
            <Head title="Notifications" />

            <div className="max-w-2xl space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Notifications"
                        description="Updates about your room, your parcels and the property."
                    />
                    {hasUnread && (
                        <Form {...NotificationController.readAll.form()}>
                            {({ processing }) => (
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={processing}
                                >
                                    Mark all as read
                                </Button>
                            )}
                        </Form>
                    )}
                </div>

                {notifications.length === 0 ? (
                    <p className="text-muted-foreground">
                        No notifications yet.
                    </p>
                ) : (
                    <ul className="divide-y rounded-md border">
                        {notifications.map((notification) => (
                            <li key={notification.id}>
                                {/* POST: membuka sekaligus menandai sudah dibaca, lalu server mengarahkan ke halamannya. */}
                                <Form
                                    {...NotificationController.open.form(
                                        notification.id,
                                    )}
                                >
                                    <button
                                        type="submit"
                                        className={cn(
                                            'flex w-full items-start gap-3 p-4 text-left text-sm hover:bg-accent',
                                            !notification.read && 'font-medium',
                                        )}
                                    >
                                        <Bell
                                            className={cn(
                                                'mt-0.5 size-4 shrink-0',
                                                notification.read
                                                    ? 'text-muted-foreground'
                                                    : 'text-primary',
                                            )}
                                        />
                                        <span className="flex-1 space-y-1">
                                            <span className="block">
                                                {notification.message}
                                            </span>
                                            {notification.createdAt !==
                                                null && (
                                                <span className="block text-xs font-normal text-muted-foreground">
                                                    {formatDateTime(
                                                        notification.createdAt,
                                                    )}
                                                </span>
                                            )}
                                        </span>
                                        {!notification.read && (
                                            <span className="sr-only">
                                                Unread
                                            </span>
                                        )}
                                    </button>
                                </Form>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </>
    );
}

Notifications.layout = {
    breadcrumbs: [
        {
            title: 'Notifications',
            href: index(),
        },
    ],
};
