import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export type AnnouncementFormValues = {
    title: string;
    body: string;
    // Format YYYY-MM-DDTHH:mm, sama dengan nilai input datetime-local.
    publishedAt: string;
    expiresAt: string | null;
};

// Kolom form pengumuman untuk modal buat dan ubah. Nama field mengikuti AnnouncementRequest di server.
export function AnnouncementFields({
    announcement,
    now,
    errors,
}: {
    announcement?: AnnouncementFormValues;
    now: string;
    errors: Partial<Record<string, string>>;
}) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="title">Title</Label>
                <Input
                    id="title"
                    name="title"
                    required
                    maxLength={100}
                    defaultValue={announcement?.title}
                    placeholder="Water off on Saturday morning"
                />
                <InputError message={errors.title} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="body">Message</Label>
                <Textarea
                    id="body"
                    name="body"
                    required
                    rows={5}
                    maxLength={5000}
                    defaultValue={announcement?.body}
                />
                <InputError message={errors.body} />
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="published_at">Show from</Label>
                    <Input
                        id="published_at"
                        name="published_at"
                        type="datetime-local"
                        required
                        defaultValue={announcement?.publishedAt ?? now}
                    />
                    <InputError message={errors.published_at} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="expires_at">Show until (optional)</Label>
                    <Input
                        id="expires_at"
                        name="expires_at"
                        type="datetime-local"
                        defaultValue={announcement?.expiresAt ?? undefined}
                    />
                    <InputError message={errors.expires_at} />
                </div>
            </div>

            <p className="text-sm text-muted-foreground">
                Times are in WIB. Set &quot;Show from&quot; to a later time to
                schedule it. Without an end time, it stays visible until you
                delete it.
            </p>
        </>
    );
}
