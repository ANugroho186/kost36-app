<?php

use App\Enums\ReminderType;
use App\Models\Tenancy;
use App\Support\ReminderSchedule;
use Carbon\CarbonImmutable;

// Hari yang dihitung: 8 November 2026. Selisih hari dihitung dari tanggal itu.
function reminderOn(array $attributes): ?ReminderType
{
    $tenancy = Tenancy::factory()->make($attributes);

    return ReminderSchedule::dueOn($tenancy, CarbonImmutable::parse('2026-11-08'))['type'] ?? null;
}

test('active tenancies get reminders on fixed days around the due date, and only then', function (string $paidUntil, ?ReminderType $expected) {
    expect(reminderOn(['paid_until' => $paidUntil]))->toBe($expected);
})->with([
    'H-4' => ['2026-11-12', null],
    'H-3' => ['2026-11-11', ReminderType::DueIn3Days],
    'H-2' => ['2026-11-10', null],
    'H-0' => ['2026-11-08', ReminderType::DueToday],
    'H+1' => ['2026-11-07', ReminderType::GraceDay1],
    'H+2' => ['2026-11-06', null],
    'H+4' => ['2026-11-04', ReminderType::GraceDay4],
    'H+7' => ['2026-11-01', ReminderType::GraceDay7],
    'H+8' => ['2026-10-31', null],
    'H+10' => ['2026-10-29', ReminderType::DecisionNeeded],
    'H+11' => ['2026-10-28', null],
]);

test('an extension replaces the usual reminders: last day for tenants, the day after for the owner', function (string $extendedUntil, ?ReminderType $expected) {
    // paid_until sudah lama lewat; selama diperpanjang, hitungan H+ tidak berlaku lagi.
    expect(reminderOn(['paid_until' => '2026-10-29', 'extended_until' => $extendedUntil]))->toBe($expected);
})->with([
    'perpanjangan masih berjalan' => ['2026-11-09', null],
    'hari terakhir perpanjangan' => ['2026-11-08', ReminderType::ExtensionLastDay],
    'sehari setelahnya' => ['2026-11-07', ReminderType::ExtensionEnded],
    'dua hari setelahnya' => ['2026-11-06', null],
]);

test('inactive tenancies only remind the owner on the vacate day', function (string $vacateBy, ?ReminderType $expected) {
    // paid_until sengaja jatuh di H+7: penghuni sewa nonaktif tidak diingatkan soal tenggang lagi.
    expect(reminderOn([
        'status' => 'inactive',
        'paid_until' => '2026-11-01',
        'deactivated_at' => '2026-10-20 10:00',
        'vacate_by' => $vacateBy,
    ]))->toBe($expected);
})->with([
    'hari pengosongan' => ['2026-11-08', ReminderType::VacateDay],
    'besok' => ['2026-11-09', null],
]);

test('ended tenancies get nothing', function () {
    expect(reminderOn(['status' => 'ended', 'paid_until' => '2026-11-08', 'end_date' => '2026-11-01']))->toBeNull();
});

test('the reference date is the date the reminder is about', function () {
    $tenancy = Tenancy::factory()->make(['paid_until' => '2026-10-29', 'extended_until' => '2026-11-07']);

    expect(ReminderSchedule::dueOn($tenancy, CarbonImmutable::parse('2026-11-08'))['dueDate']->toDateString())->toBe('2026-11-07');
});
