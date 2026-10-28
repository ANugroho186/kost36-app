import { Form, usePage } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { useState } from 'react';
import OccupantAccountController from '@/actions/App/Http/Controllers/Owner/OccupantAccountController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type OccupantRef = {
    tenancyId: number;
    occupantId: number;
};

type TemporaryPasswordFlash = {
    username: string;
    password: string;
};

function isTemporaryPassword(value: unknown): value is TemporaryPasswordFlash {
    return (
        typeof value === 'object' &&
        value !== null &&
        typeof (value as Record<string, unknown>).username === 'string' &&
        typeof (value as Record<string, unknown>).password === 'string'
    );
}

/**
 * Password sementara datang sebagai flash: hanya ada di tampilan tepat setelah akun dibuat
 * atau direset, lalu hilang. Server tidak menyimpannya dalam bentuk asli.
 */
export function TemporaryPasswordNotice() {
    const { flash } = usePage();
    const [copied, setCopied] = useState(false);
    const data = flash.temporaryPassword;

    if (!isTemporaryPassword(data)) {
        return null;
    }

    const copy = () => {
        void navigator.clipboard.writeText(data.password).then(() => {
            setCopied(true);
        });
    };

    return (
        <Alert>
            <KeyRound />
            <AlertTitle>Temporary password for {data.username}</AlertTitle>
            <AlertDescription className="space-y-3">
                <div className="flex flex-wrap items-center gap-3">
                    <code className="rounded bg-muted px-2 py-1 font-mono text-base text-foreground">
                        {data.password}
                    </code>
                    <Button size="sm" variant="outline" onClick={copy}>
                        {copied ? 'Copied' : 'Copy'}
                    </Button>
                </div>
                <p>
                    This password is shown only once. Give it to the tenant in
                    person or by private message. They will be reminded to
                    change it after logging in.
                </p>
            </AlertDescription>
        </Alert>
    );
}

export function CreateAccountDialog({
    tenancyId,
    occupantId,
    nickname,
}: OccupantRef & { nickname: string | null }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    Create login
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        Create a login for {nickname ?? 'this occupant'}
                    </DialogTitle>
                    <DialogDescription>
                        A temporary password is generated for you to pass on.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...OccupantAccountController.store.form({
                        tenancy: tenancyId,
                        occupant: occupantId,
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="username">Username</Label>
                                <Input
                                    id="username"
                                    name="username"
                                    required
                                    minLength={3}
                                    maxLength={30}
                                    autoCapitalize="none"
                                    autoComplete="off"
                                />
                                <p className="text-sm text-muted-foreground">
                                    Letters, numbers, dashes and underscores.
                                </p>
                                <InputError message={errors.username} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">
                                    Email address (optional)
                                </Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    autoComplete="off"
                                />
                                <InputError message={errors.email} />
                            </div>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button disabled={processing}>
                                    Create login
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

export function ResetPasswordDialog({
    tenancyId,
    occupantId,
    username,
}: OccupantRef & { username: string | null }) {
    const [open, setOpen] = useState(false);

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button size="sm" variant="outline">
                    Reset password
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Reset the password of {username}?</DialogTitle>
                    <DialogDescription>
                        A new temporary password is generated, and the tenant is
                        logged out on every device.
                    </DialogDescription>
                </DialogHeader>
                <Form
                    {...OccupantAccountController.resetPassword.form({
                        tenancy: tenancyId,
                        occupant: occupantId,
                    })}
                    options={{ preserveScroll: true }}
                    onSuccess={() => setOpen(false)}
                    resetOnError
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="owner_password">
                                    Your password
                                </Label>
                                <PasswordInput
                                    id="owner_password"
                                    name="owner_password"
                                    required
                                    autoComplete="current-password"
                                />
                                <InputError message={errors.owner_password} />
                            </div>
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Cancel
                                    </Button>
                                </DialogClose>
                                <Button
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Reset password
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
