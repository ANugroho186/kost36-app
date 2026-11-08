<?php

use App\Models\Announcement;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Notifications\AnnouncementPublished;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => $this->travelTo('2026-11-08 18:00:00'));

test('a live announcement reaches everyone living in that property, once', function () {
    $scenario = accessScenario();
    $property = $scenario['tenancy']->room->property;
    $inactive = TenancyOccupant::factory()
        ->for(Tenancy::factory()->inactive()->for(Room::factory()->for($property)))
        ->create()->user;
    $elsewhere = TenancyOccupant::factory()->create()->user;
    $announcement = Announcement::factory()->for($property)->create(['title' => 'Air mati hari Sabtu', 'published_at' => '2026-11-08 17:59']);

    $this->artisan('announcements:notify')->assertSuccessful()->expectsOutputToContain('1 notified');

    foreach ([$scenario['occupant'], $scenario['roommate'], $scenario['otherTenant'], $inactive] as $tenant) {
        $stored = $tenant->notifications()->sole();
        expect($stored->type)->toBe(AnnouncementPublished::class)
            ->and($stored->data['message'])->toBe('New announcement: “Air mati hari Sabtu”')
            ->and($stored->data['url'])->toBe('/announcements');
    }

    expect($scenario['formerOccupant']->notifications()->count())->toBe(0)
        ->and($scenario['owner']->notifications()->count())->toBe(0)
        ->and($elsewhere->notifications()->count())->toBe(0)
        ->and($announcement->fresh()->notified_at)->not->toBeNull();

    $this->artisan('announcements:notify')->assertSuccessful()->expectsOutputToContain('0 notified');

    expect($scenario['occupant']->notifications()->count())->toBe(1);
});

test('a scheduled announcement is announced only once its time comes', function () {
    $scenario = accessScenario();
    $announcement = Announcement::factory()->for($scenario['tenancy']->room->property)->create(['published_at' => '2026-11-08 18:30']);

    $this->artisan('announcements:notify');
    expect($scenario['occupant']->notifications()->count())->toBe(0);

    $this->travelTo('2026-11-08 18:30:00');
    $this->artisan('announcements:notify');

    expect($scenario['occupant']->notifications()->count())->toBe(1)
        ->and($announcement->fresh()->notified_at)->not->toBeNull();
});

test('expired announcements are never announced', function () {
    $scenario = accessScenario();
    Announcement::factory()->for($scenario['tenancy']->room->property)->expired()->create();

    $this->artisan('announcements:notify');

    expect($scenario['occupant']->notifications()->count())->toBe(0);
});

test('editing an announced announcement does not announce it again', function () {
    $scenario = accessScenario();
    $announcement = Announcement::factory()->for($scenario['tenancy']->room->property)->create(['published_at' => '2026-11-08 17:00']);
    $this->artisan('announcements:notify');

    $this->actingAs($scenario['owner'])->put(route('owner.announcements.update', $announcement), [
        'title' => 'Judul diperbaiki',
        'body' => 'Isi diperbaiki.',
        'published_at' => '2026-11-08T17:00',
        // Diabaikan: notified_at tidak fillable.
        'notified_at' => null,
    ]);
    $this->artisan('announcements:notify');

    expect($scenario['occupant']->notifications()->count())->toBe(1)
        ->and($announcement->fresh()->notified_at)->not->toBeNull();
});

test('a failed send leaves the announcement unannounced, so the next minute tries again', function () {
    $scenario = accessScenario();
    $announcement = Announcement::factory()->for($scenario['tenancy']->room->property)->create(['published_at' => '2026-11-08 17:00']);
    Event::listen(NotificationSending::class, fn () => throw new RuntimeException('Pengiriman gagal.'));

    $this->artisan('announcements:notify')->assertFailed();

    expect($announcement->fresh()->notified_at)->toBeNull();
});

test('announcements are queued inside the same transaction as the claim', function () {
    Notification::fake();
    $scenario = accessScenario();
    Announcement::factory()->for($scenario['tenancy']->room->property)->create(['published_at' => '2026-11-08 17:00']);

    $this->artisan('announcements:notify');

    Notification::assertSentTo(
        $scenario['occupant'],
        AnnouncementPublished::class,
        fn (AnnouncementPublished $notification) => $notification->afterCommit === false,
    );
});

test('the demo data does not flood demo tenants with old announcements', function () {
    $this->seed(DemoSeeder::class);
    Notification::fake();

    $this->artisan('announcements:notify')->expectsOutputToContain('0 notified');

    Notification::assertNothingSent();
});

test('announcements are checked every minute', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'announcements:notify'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('* * * * *')
        // Kunci yang tertinggal (proses mati di tengah jalan) hilang sendiri setelah 5 menit, bukan 24 jam.
        ->and($event->expiresAt)->toBe(5);
});
