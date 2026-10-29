import { Head } from '@inertiajs/react';
import TenancyOccupantController from '@/actions/App/Http/Controllers/Owner/TenancyOccupantController';
import { FormDialog } from '@/components/form-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    CreateAccountDialog,
    ResetPasswordDialog,
    TemporaryPasswordNotice,
} from '@/components/occupant-account-actions';
import { RoommateConsentForm } from '@/components/roommate-consent-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDate } from '@/lib/dates';
import { index } from '@/routes/owner/tenancies';

// Sama persis dengan yang dikirim TenancyController::show().
type Props = {
    tenancy: {
        id: number;
        roomNumber: string;
        capacity: number;
        status: 'active' | 'inactive';
        startDate: string;
        paidUntil: string;
        vacateBy: string | null;
        acceptsRoommate: boolean;
        occupants: {
            id: number;
            nickname: string | null;
            movedInAt: string;
            hasAccount: boolean;
            username: string | null;
        }[];
    };
    canManageAccounts: boolean;
    canAddOccupant: boolean;
    canToggleRoommate: boolean;
    today: string;
};

export default function ShowTenancy({
    tenancy,
    canManageAccounts,
    canAddOccupant,
    canToggleRoommate,
    today,
}: Props) {
    const isFull = tenancy.occupants.length >= tenancy.capacity;

    return (
        <>
            <Head title={`Room ${tenancy.roomNumber}`} />

            <div className="max-w-2xl space-y-10 p-4">
                <TemporaryPasswordNotice />

                <section className="space-y-3">
                    <div className="flex flex-wrap items-center gap-3">
                        <Heading title={`Room ${tenancy.roomNumber}`} />
                    </div>
                    <dl className="grid gap-2 text-sm sm:grid-cols-2">
                        <div>
                            <dt className="text-muted-foreground">Status</dt>
                            <dd className="font-medium capitalize">
                                {tenancy.status}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">Started</dt>
                            <dd className="font-medium">
                                {formatDate(tenancy.startDate)}
                            </dd>
                        </div>
                        <div>
                            <dt className="text-muted-foreground">
                                Next payment due
                            </dt>
                            <dd className="font-medium">
                                {formatDate(tenancy.paidUntil)}
                            </dd>
                        </div>
                        {tenancy.vacateBy !== null && (
                            <div>
                                <dt className="text-muted-foreground">
                                    Vacate by
                                </dt>
                                <dd className="font-medium">
                                    {formatDate(tenancy.vacateBy)}
                                </dd>
                            </div>
                        )}
                    </dl>
                </section>

                <section className="space-y-3 border-t pt-6">
                    <Heading
                        variant="small"
                        title={`Occupants (${tenancy.occupants.length} / ${tenancy.capacity})`}
                    />
                    <ul className="space-y-2 text-sm">
                        {tenancy.occupants.map((occupant) => (
                            <li
                                key={occupant.id}
                                className="flex flex-wrap items-center gap-2"
                            >
                                <span className="font-medium">
                                    {occupant.nickname ?? 'Unnamed'}
                                </span>
                                <span className="text-muted-foreground">
                                    since {formatDate(occupant.movedInAt)}
                                </span>
                                <Badge variant="outline">
                                    {occupant.hasAccount
                                        ? `Login: ${occupant.username}`
                                        : 'No account'}
                                </Badge>
                                {canManageAccounts &&
                                    (occupant.hasAccount ? (
                                        <ResetPasswordDialog
                                            tenancyId={tenancy.id}
                                            occupantId={occupant.id}
                                            username={occupant.username}
                                        />
                                    ) : (
                                        <CreateAccountDialog
                                            tenancyId={tenancy.id}
                                            occupantId={occupant.id}
                                            nickname={occupant.nickname}
                                        />
                                    ))}
                            </li>
                        ))}
                    </ul>
                    {canAddOccupant && (
                        <FormDialog
                            trigger={
                                <Button variant="outline" size="sm">
                                    Add occupant
                                </Button>
                            }
                            title={`Add an occupant to room ${tenancy.roomNumber}`}
                            description="Their payment date follows this room's tenancy."
                            action={TenancyOccupantController.store.form(
                                tenancy.id,
                            )}
                            submitLabel="Add occupant"
                        >
                            {({ errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="nickname">
                                            Nickname
                                        </Label>
                                        <Input
                                            id="nickname"
                                            name="nickname"
                                            required
                                            maxLength={50}
                                        />
                                        <InputError message={errors.nickname} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="moved_in_at">
                                            Moved in on
                                        </Label>
                                        <Input
                                            id="moved_in_at"
                                            name="moved_in_at"
                                            type="date"
                                            required
                                            defaultValue={today}
                                        />
                                        <InputError
                                            message={errors.moved_in_at}
                                        />
                                    </div>
                                </>
                            )}
                        </FormDialog>
                    )}
                </section>

                {!isFull && tenancy.status === 'active' && (
                    <section className="space-y-3 border-t pt-6">
                        <Heading
                            variant="small"
                            title="Sharing the room"
                            description={
                                tenancy.acceptsRoommate
                                    ? 'Open for one more person the occupant knows. This resets after the next person is added.'
                                    : 'Marked as full. A second person can only be added once the room is opened for sharing, by the occupant or by you.'
                            }
                        />
                        {canToggleRoommate && (
                            <RoommateConsentForm
                                tenancyId={tenancy.id}
                                accepts={tenancy.acceptsRoommate}
                            />
                        )}
                    </section>
                )}
            </div>
        </>
    );
}

ShowTenancy.layout = {
    breadcrumbs: [
        {
            title: 'Tenancies',
            href: index(),
        },
    ],
};
