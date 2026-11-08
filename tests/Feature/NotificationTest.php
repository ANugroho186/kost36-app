<?php

use App\Models\Property;
use App\Models\User;
use App\Notifications\RentalInquiryReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Notifikasi portal buatan sendiri, untuk menguji halaman tanpa bergantung pada kejadian tertentu.
 *
 * @param  array<string, string>  $data
 */
function portalNotification(User $user, array $data = [], bool $read = false, ?string $createdAt = null): DatabaseNotification
{
    return $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => RentalInquiryReceived::class,
        'data' => ['message' => 'Contoh notifikasi', 'url' => '/dashboard', ...$data],
        'read_at' => $read ? now() : null,
        'created_at' => $createdAt ?? now(),
    ]);
}

test('a new viewing request notifies the property owner, and nobody else', function () {
    Notification::fake();
    $property = Property::factory()->create();
    $tenant = accessScenario()['occupant'];

    $this->post(route('rental-inquiries.store'), ['name' => 'Rina', 'contact' => 'rina@example.com'])
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($property->owner, RentalInquiryReceived::class);
    Notification::assertNotSentTo($tenant, RentalInquiryReceived::class);
});

test('notifications are queued and store only a message and an in-app link', function () {
    $property = Property::factory()->create();

    $this->post(route('rental-inquiries.store'), ['name' => 'Rina Contoh', 'contact' => 'rina@example.com']);

    $stored = $property->owner->notifications()->sole();

    expect(new RentalInquiryReceived)->toBeInstanceOf(ShouldQueue::class)
        ->and(array_keys($stored->data))->toBe(['message', 'url'])
        ->and($stored->data['url'])->toBe('/owner/rental-inquiries')
        // Nama dan kontak calon penyewa tidak ikut tersimpan di notifikasi.
        ->and(json_encode($stored->data))->not->toContain('Rina')->not->toContain('rina@example.com');
});

test('people see only their own notifications, newest first', function () {
    $user = User::factory()->create();
    $older = portalNotification($user, ['message' => 'Lama'], read: true, createdAt: now()->subDay());
    $newer = portalNotification($user, ['message' => 'Baru']);
    portalNotification(User::factory()->create(), ['message' => 'Milik orang lain']);

    // Tanpa ->etc(): url tidak perlu dikirim, karena tautan dibuka lewat route open.
    $this->actingAs($user)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('notifications/index')
            ->where('unreadNotifications', 1)
            ->has('notifications', 2)
            ->has('notifications.0', fn (Assert $item) => $item
                ->where('id', $newer->id)
                ->where('message', 'Baru')
                ->where('createdAt', $newer->created_at->format('Y-m-d\TH:i'))
                ->where('read', false),
            )
            ->where('notifications.1.id', $older->id)
            ->where('notifications.1.read', true),
        );
});

test('opening a notification marks it as read and goes to its page', function () {
    $user = User::factory()->create();
    $notification = portalNotification($user, ['url' => '/owner/rental-inquiries']);

    $this->actingAs($user)
        ->post(route('notifications.open', $notification->id))
        ->assertRedirect('/owner/rental-inquiries');

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('a notification of someone else cannot be opened', function () {
    $notification = portalNotification(User::factory()->create());

    $this->actingAs(User::factory()->create())
        ->post(route('notifications.open', $notification->id))
        ->assertNotFound();

    expect($notification->fresh()->read_at)->toBeNull();
});

test('a notification never sends anyone to another site', function (string $url) {
    $user = User::factory()->create();
    $notification = portalNotification($user, ['url' => $url]);

    $this->actingAs($user)
        ->post(route('notifications.open', $notification->id))
        ->assertRedirect(route('notifications.index'));
})->with([
    'alamat lengkap' => 'https://situs-lain.example',
    'tanpa skema' => '//situs-lain.example',
    'kosong' => '',
]);

test('mark all as read only touches the user\'s own notifications', function () {
    $user = User::factory()->create();
    portalNotification($user);
    portalNotification($user);
    $someoneElses = portalNotification(User::factory()->create());

    $this->actingAs($user)
        ->post(route('notifications.read-all'))
        ->assertRedirect(route('notifications.index'));

    expect($user->unreadNotifications()->count())->toBe(0)
        ->and($someoneElses->fresh()->read_at)->toBeNull();
});

test('guests are sent to the login page', function () {
    $this->get(route('notifications.index'))->assertRedirect(route('login'));
});
