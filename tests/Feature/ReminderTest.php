<?php

use App\Enums\ReminderType;
use App\Models\ReminderLog;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Notifications\RentReminder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => $this->travelTo('2026-11-08 08:00:00'));

test('a due reminder reaches the people living in that room, with the room and date only', function () {
    $scenario = accessScenario();
    $scenario['tenancy']->update(['paid_until' => '2026-11-11']);
    // Penghuni tanpa akun tetap tercatat, tapi tidak bisa menerima notifikasi portal.
    TenancyOccupant::factory()->for($scenario['tenancy'])->create(['nickname' => 'Eko']);

    $this->artisan('reminders:send')->assertSuccessful();

    $stored = $scenario['occupant']->notifications()->sole();

    expect($stored->type)->toBe(RentReminder::class)
        ->and($stored->data['message'])->toBe("Rent for room {$scenario['tenancy']->room->number} is due on 11 November 2026.")
        ->and($stored->data['url'])->toBe('/dashboard')
        ->and($scenario['roommate']->notifications()->count())->toBe(1)
        ->and($scenario['formerOccupant']->notifications()->count())->toBe(0)
        ->and($scenario['owner']->notifications()->count())->toBe(0);

    // Tetangga kamar lain punya jatuh tempo sendiri (bawaan factory: besok dan seterusnya).
    expect($scenario['otherTenant']->notifications()->where('type', RentReminder::class)->count())
        ->toBe(ReminderLog::query()->where('tenancy_id', $scenario['otherTenant']->currentTenancy()->id)->count());
});

test('the owner is asked for a decision on day ten, and tenants are not', function () {
    $scenario = accessScenario();
    $scenario['tenancy']->update(['paid_until' => '2026-10-29']);

    $this->artisan('reminders:send')->assertSuccessful();

    $owner = $scenario['tenancy']->room->property->owner;
    $stored = $owner->notifications()->sole();

    expect($stored->data['url'])->toBe("/owner/tenancies/{$scenario['tenancy']->id}")
        ->and($stored->data['message'])->toContain('has not paid since 29 October 2026')
        ->and($scenario['occupant']->notifications()->count())->toBe(0);
});

test('running the command twice on the same day sends each reminder once', function () {
    $scenario = accessScenario();
    $scenario['tenancy']->update(['paid_until' => '2026-11-08']);

    $this->artisan('reminders:send')->assertSuccessful();
    $this->artisan('reminders:send')->assertSuccessful()->expectsOutputToContain('1 already sent');

    $log = ReminderLog::query()->where('tenancy_id', $scenario['tenancy']->id)->sole();

    expect($log->type)->toBe(ReminderType::DueToday)
        ->and($log->due_date->toDateString())->toBe('2026-11-08')
        ->and($scenario['occupant']->notifications()->count())->toBe(1);
});

test('a reminder already logged, for example by the demo data, is not sent again', function () {
    $scenario = accessScenario();
    $scenario['tenancy']->update(['paid_until' => '2026-11-07']);
    ReminderLog::factory()->for($scenario['tenancy'])->create(['due_date' => '2026-11-07', 'type' => ReminderType::GraceDay1]);

    $this->artisan('reminders:send')->assertSuccessful();

    expect($scenario['occupant']->notifications()->count())->toBe(0);
});

test('a reminder that fails to send is not logged, so the next run tries again, and others still go out', function () {
    $failing = TenancyOccupant::factory()->for(Tenancy::factory()->create(['paid_until' => '2026-11-08']))->create();
    $fine = TenancyOccupant::factory()->for(Tenancy::factory()->for(Room::factory())->create(['paid_until' => '2026-11-08']))->create();
    Event::listen(NotificationSending::class, function (NotificationSending $event) use ($failing): void {
        if ($event->notifiable->is($failing->user)) {
            throw new RuntimeException('Pengiriman gagal.');
        }
    });

    $this->artisan('reminders:send')->assertFailed()->expectsOutputToContain('1 sent, 0 already sent, 1 failed');

    // Log dan notifikasi tersimpan bersama atau batal bersama.
    expect(ReminderLog::query()->where('tenancy_id', $failing->tenancy_id)->exists())->toBeFalse()
        ->and(ReminderLog::query()->where('tenancy_id', $fine->tenancy_id)->exists())->toBeTrue()
        ->and($fine->user->notifications()->count())->toBe(1);
});

test('reminders are queued inside the same transaction as their log', function () {
    Notification::fake();
    $scenario = accessScenario();
    $scenario['tenancy']->update(['paid_until' => '2026-11-08']);

    $this->artisan('reminders:send')->assertSuccessful();

    // Antrean di test berjalan sync, yang tidak menunda job sampai commit. Karena itu niatnya diperiksa
    // langsung: tanpa beforeCommit(), config after_commit akan memisahkan log dari notifikasinya.
    Notification::assertSentTo(
        $scenario['occupant'],
        RentReminder::class,
        fn (RentReminder $notification) => $notification->afterCommit === false,
    );
});

test('a chosen date can be used to try reminders locally', function () {
    $scenario = accessScenario();
    $scenario['tenancy']->update(['paid_until' => '2026-11-20']);

    $this->artisan('reminders:send', ['--date' => '2026-11-17'])->assertSuccessful();

    expect(ReminderLog::query()->where('tenancy_id', $scenario['tenancy']->id)->sole()->type)->toBe(ReminderType::DueIn3Days);
});

test('an invalid date is refused', function (string $date) {
    $this->artisan('reminders:send', ['--date' => $date])->assertExitCode(2);

    expect(ReminderLog::query()->count())->toBe(0);
})->with(['besok', '2026-13-01', '08-11-2026']);

test('reminders are scheduled every day at 08:00', function () {
    $event = collect(app(Schedule::class)->events())
        ->first(fn ($event) => str_contains((string) $event->command, 'reminders:send'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('0 8 * * *')
        // Kunci yang tertinggal (proses mati di tengah jalan) hilang sendiri setelah 60 menit, bukan 24 jam.
        ->and($event->expiresAt)->toBe(60);
});
