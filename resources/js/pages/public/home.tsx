import { Form, Head, Link, usePage } from '@inertiajs/react';
import { BedDouble, MapPin, ScrollText, Users } from 'lucide-react';
import RentalInquiryController from '@/actions/App/Http/Controllers/RentalInquiryController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { dashboard, login } from '@/routes';

// Bentuk data ini sama persis dengan yang dikirim PublicPropertyController.
// Tidak ada data penghuni: halaman ini bisa dibuka siapa pun.
type PublicProperty = {
    name: string;
    address: string;
    description: string;
    rules: string;
};

type PublicRoom = {
    number: string;
    capacity: number;
    facilities: string;
    isAvailable: boolean;
    photos: string[];
};

type Props = {
    property: PublicProperty | null;
    rooms: PublicRoom[];
    summary: { available: number; total: number };
};

function capacityLabel(capacity: number): string {
    return capacity === 1 ? 'For 1 person' : `For 1–${capacity} people`;
}

function mapsUrl(address: string): string {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`;
}

function ViewingRequestForm() {
    // Format YYYY-MM-DD menurut tanggal di perangkat pengunjung, untuk batas bawah kalender.
    const today = new Date().toLocaleDateString('en-CA');

    return (
        <Card>
            <CardHeader>
                <CardTitle>
                    <h2 id="viewing-heading">Request a viewing</h2>
                </CardTitle>
                <CardDescription>
                    Leave your contact details and we will get back to you to
                    arrange a visit.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <Form
                    {...RentalInquiryController.store.form()}
                    resetOnSuccess
                    options={{ preserveScroll: true }}
                    className="space-y-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    maxLength={100}
                                    autoComplete="name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="contact">
                                    WhatsApp number or email
                                </Label>
                                <Input
                                    id="contact"
                                    name="contact"
                                    required
                                    maxLength={100}
                                />
                                <InputError message={errors.contact} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="preferred_date">
                                    Preferred viewing date (optional)
                                </Label>
                                <Input
                                    id="preferred_date"
                                    name="preferred_date"
                                    type="date"
                                    min={today}
                                />
                                <InputError message={errors.preferred_date} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="message">
                                    Message (optional)
                                </Label>
                                <Textarea
                                    id="message"
                                    name="message"
                                    rows={3}
                                    maxLength={1000}
                                />
                                <InputError message={errors.message} />
                            </div>

                            <p className="text-xs text-muted-foreground">
                                We only use these details to arrange your
                                viewing, and delete them 30 days after we are
                                done.
                            </p>

                            <Button
                                type="submit"
                                disabled={processing}
                                className="w-full sm:w-auto"
                            >
                                {processing && <Spinner />}
                                Send request
                            </Button>
                        </>
                    )}
                </Form>
            </CardContent>
        </Card>
    );
}

export default function Home({ property, rooms, summary }: Props) {
    const { auth } = usePage().props;
    const title = property?.name ?? 'Welcome';

    return (
        <>
            <Head title={title} />

            <div className="min-h-screen bg-background text-foreground">
                <header className="border-b">
                    <div className="mx-auto flex max-w-4xl items-center justify-between px-4 py-4">
                        <span className="font-semibold">{title}</span>
                        <Link
                            href={auth.user ? dashboard() : login()}
                            className="text-sm font-medium underline-offset-4 hover:underline"
                        >
                            {auth.user ? 'Dashboard' : 'Log in'}
                        </Link>
                    </div>
                </header>

                <main className="mx-auto max-w-4xl space-y-10 px-4 py-8">
                    {property === null ? (
                        <p className="text-muted-foreground">
                            This property hasn&apos;t been set up yet.
                        </p>
                    ) : (
                        <>
                            <section className="space-y-3">
                                <h1 className="text-2xl font-semibold tracking-tight sm:text-3xl">
                                    {property.name}
                                </h1>
                                <p className="whitespace-pre-line text-muted-foreground">
                                    {property.description}
                                </p>
                                <p className="text-sm font-medium">
                                    {summary.available} of {summary.total} rooms
                                    available
                                </p>
                            </section>

                            <section
                                aria-labelledby="rooms-heading"
                                className="space-y-4"
                            >
                                <h2
                                    id="rooms-heading"
                                    className="flex items-center gap-2 text-lg font-semibold"
                                >
                                    <BedDouble className="size-5" />
                                    Rooms
                                </h2>

                                {rooms.length === 0 ? (
                                    <p className="text-muted-foreground">
                                        No rooms are listed yet.
                                    </p>
                                ) : (
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        {rooms.map((room) => (
                                            <Card key={room.number}>
                                                {room.photos.length > 0 && (
                                                    <img
                                                        src={room.photos[0]}
                                                        alt={`Room ${room.number}`}
                                                        loading="lazy"
                                                        className="-mt-6 aspect-video w-full rounded-t-xl object-cover"
                                                    />
                                                )}
                                                <CardHeader>
                                                    <div className="flex items-center justify-between gap-2">
                                                        <CardTitle>
                                                            Room {room.number}
                                                        </CardTitle>
                                                        <Badge
                                                            variant={
                                                                room.isAvailable
                                                                    ? 'default'
                                                                    : 'secondary'
                                                            }
                                                        >
                                                            {room.isAvailable
                                                                ? 'Available'
                                                                : 'Occupied'}
                                                        </Badge>
                                                    </div>
                                                    <CardDescription className="flex items-center gap-1.5">
                                                        <Users className="size-4" />
                                                        {capacityLabel(
                                                            room.capacity,
                                                        )}
                                                    </CardDescription>
                                                </CardHeader>
                                                <CardContent>
                                                    <p className="text-sm whitespace-pre-line">
                                                        {room.facilities}
                                                    </p>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                )}
                            </section>

                            <section aria-labelledby="viewing-heading">
                                <ViewingRequestForm />
                            </section>

                            <section
                                aria-labelledby="rules-heading"
                                className="space-y-3"
                            >
                                <h2
                                    id="rules-heading"
                                    className="flex items-center gap-2 text-lg font-semibold"
                                >
                                    <ScrollText className="size-5" />
                                    House rules
                                </h2>
                                <p className="whitespace-pre-line text-muted-foreground">
                                    {property.rules}
                                </p>
                            </section>

                            <section
                                aria-labelledby="location-heading"
                                className="space-y-3"
                            >
                                <h2
                                    id="location-heading"
                                    className="flex items-center gap-2 text-lg font-semibold"
                                >
                                    <MapPin className="size-5" />
                                    Location
                                </h2>
                                <p className="whitespace-pre-line text-muted-foreground">
                                    {property.address}
                                </p>
                                <a
                                    href={mapsUrl(property.address)}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-block text-sm font-medium underline underline-offset-4"
                                >
                                    Open in Google Maps
                                </a>
                            </section>
                        </>
                    )}
                </main>

                <footer className="border-t">
                    <div className="mx-auto max-w-4xl px-4 py-6 text-sm text-muted-foreground">
                        © {new Date().getFullYear()} {title}
                    </div>
                </footer>
            </div>
        </>
    );
}
