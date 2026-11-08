import { Form, Head } from '@inertiajs/react';
import type { ReactNode } from 'react';
import NotificationSettingsController from '@/actions/App/Http/Controllers/Settings/NotificationSettingsController';
import TelegramConnectionController from '@/actions/App/Http/Controllers/Settings/TelegramConnectionController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { edit as editNotificationSettings } from '@/routes/notification-settings';
import { edit as editProfile } from '@/routes/profile';

// Sama dengan App\Enums\NotifyChannel. null = di aplikasi saja.
type Channel = 'email' | 'telegram' | null;

type Props = {
    channel: Channel;
    email: string | null;
    telegramConnected: boolean;
    // false kalau bot belum diatur di .env: pilihan Telegram tidak ditampilkan.
    telegramAvailable: boolean;
};

// Di luar form pilihan saluran, karena form tidak boleh bersarang di dalam form lain.
function TelegramConnection({ connected }: { connected: boolean }) {
    if (connected) {
        return (
            <div className="flex flex-wrap items-center justify-between gap-3 rounded-md border p-4 text-sm">
                <span>Telegram is connected to this account.</span>
                <Form
                    {...TelegramConnectionController.destroy.form()}
                    options={{ preserveScroll: true }}
                >
                    {({ processing }) => (
                        <Button
                            size="sm"
                            variant="outline"
                            disabled={processing}
                        >
                            Disconnect
                        </Button>
                    )}
                </Form>
            </div>
        );
    }

    return (
        <div className="space-y-3 rounded-md border p-4 text-sm">
            <p>
                Connect Telegram to receive notifications there. A chat with our
                bot opens: press <span className="font-medium">Start</span>,
                then come back and refresh this page. The link works once, for
                15 minutes.
            </p>
            <Form {...TelegramConnectionController.store.form()}>
                {({ processing }) => (
                    <Button size="sm" disabled={processing}>
                        Connect Telegram
                    </Button>
                )}
            </Form>
        </div>
    );
}

function ChannelOption({
    value,
    title,
    description,
    checked,
    disabled = false,
}: {
    value: string;
    title: string;
    description: ReactNode;
    checked: boolean;
    disabled?: boolean;
}) {
    const id = `notify_channel_${value || 'portal'}`;

    return (
        <label
            htmlFor={id}
            className="flex cursor-pointer items-start gap-3 rounded-md border p-4 has-checked:border-primary has-disabled:cursor-not-allowed has-disabled:opacity-60"
        >
            <input
                id={id}
                type="radio"
                name="notify_channel"
                value={value}
                defaultChecked={checked}
                disabled={disabled}
                className="mt-1 accent-primary"
            />
            <span className="space-y-1">
                <span className="block text-sm font-medium">{title}</span>
                <span className="block text-sm text-muted-foreground">
                    {description}
                </span>
            </span>
        </label>
    );
}

export default function NotificationSettings({
    channel,
    email,
    telegramConnected,
    telegramAvailable,
}: Props) {
    return (
        <>
            <Head title="Notification settings" />

            <h1 className="sr-only">Notification settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Notification settings"
                    description="Notifications always appear in the app. You can also receive them somewhere else."
                />

                <Form
                    {...NotificationSettingsController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-3">
                                {/* Nilai kosong menjadi null di server: hanya di aplikasi. */}
                                <ChannelOption
                                    value=""
                                    title="In the app only"
                                    description="See them in the Notifications menu."
                                    checked={channel === null}
                                />
                                <ChannelOption
                                    value="email"
                                    title="Email"
                                    description={
                                        email !== null ? (
                                            <>Also send them to {email}.</>
                                        ) : (
                                            <>
                                                <TextLink href={editProfile()}>
                                                    Add an email address
                                                </TextLink>{' '}
                                                to your profile first.
                                            </>
                                        )
                                    }
                                    checked={channel === 'email'}
                                    disabled={email === null}
                                />
                                {telegramAvailable && (
                                    <ChannelOption
                                        value="telegram"
                                        title="Telegram"
                                        description={
                                            telegramConnected
                                                ? 'Also send them to your Telegram.'
                                                : 'Connect Telegram below first.'
                                        }
                                        checked={channel === 'telegram'}
                                        disabled={!telegramConnected}
                                    />
                                )}
                            </div>
                            <InputError message={errors.notify_channel} />

                            <Button disabled={processing}>Save</Button>
                        </>
                    )}
                </Form>

                {telegramAvailable && (
                    <TelegramConnection connected={telegramConnected} />
                )}
            </div>
        </>
    );
}

NotificationSettings.layout = {
    breadcrumbs: [
        {
            title: 'Notification settings',
            href: editNotificationSettings(),
        },
    ],
};
