<?php

use App\Models\Parcel;
use App\Models\ParcelReport;
use App\Models\TenancyOccupant;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// Sisi penghuni yang salah menerima paket.

test('a tenant reports a parcel that is not theirs', function () {
    $scenario = accessScenario();
    $someoneElses = Parcel::factory()->for($scenario['roommate'])->create();

    $this->actingAs($scenario['occupant'])
        ->post(route('parcel-reports.store'), [
            'description' => 'Paket resi JNE123 untuk Rina.',
            // Diabaikan: pelapor tidak boleh menautkan atau menyelesaikan laporannya sendiri.
            'parcel_id' => $someoneElses->id,
            'resolved_at' => now()->toDateTimeString(),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('parcels.index'));

    $report = ParcelReport::query()->sole();

    expect($report->reporter_id)->toBe($scenario['occupant']->id)
        ->and($report->description)->toBe('Paket resi JNE123 untuk Rina.')
        ->and($report->parcel_id)->toBeNull()
        ->and($report->resolved_at)->toBeNull();
});

test('a report needs a description', function (string $description) {
    $this->actingAs(accessScenario()['occupant'])
        ->post(route('parcel-reports.store'), ['description' => $description])
        ->assertInvalid('description');

    expect(ParcelReport::query()->count())->toBe(0);
})->with(['kosong' => '', 'terlalu panjang' => str_repeat('a', 501)]);

test('only people living in a room can report', function (string $actor) {
    $scenario = accessScenario();

    $this->actingAs($scenario[$actor])->post(route('parcel-reports.store'), [])->assertForbidden();

    expect(ParcelReport::query()->count())->toBe(0);
})->with([
    'pemilik kost' => ['owner'],
    'penghuni yang sudah keluar' => ['formerOccupant'],
]);

test('the reporter follows their own reports without seeing whose parcel it is', function () {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['otherTenant'])->create();
    $report = ParcelReport::factory()->for($scenario['occupant'], 'reporter')->create([
        'description' => 'Paket resi JNE123.',
        'parcel_id' => $parcel->id,
    ]);
    // Laporan teman sekamar tidak ikut.
    ParcelReport::factory()->for($scenario['roommate'], 'reporter')->create();

    // Tanpa ->etc(): paket yang ditautkan dan pemiliknya tidak boleh ikut terkirim.
    $this->actingAs($scenario['occupant'])
        ->get(route('parcels.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('canReport', true)
            ->has('reports', 1)
            ->has('reports.0', fn (Assert $item) => $item
                ->where('id', $report->id)
                ->where('description', 'Paket resi JNE123.')
                ->where('reportedAt', $report->created_at->toDateString())
                ->where('status', 'linked'),
            ),
        );
});

// Sisi penghuni pemilik paket.

test('the parcel owner sees that their parcel was received by mistake, but not by whom', function () {
    $scenario = accessScenario();
    $reporter = TenancyOccupant::factory()->create(['nickname' => 'Citra'])->user;
    $parcel = Parcel::factory()->for($scenario['occupant'])->create();
    ParcelReport::factory()->for($reporter, 'reporter')->create([
        'description' => 'Paket untuk Budi ada di kamar saya.',
        'parcel_id' => $parcel->id,
    ]);

    $this->actingAs($scenario['occupant'])
        ->get(route('parcels.index'))
        ->assertInertia(fn (Assert $page) => $page->where('parcels.0.misdelivered', true))
        ->assertDontSee('Citra')
        ->assertDontSee('ada di kamar saya');
});

test('the misdelivered note disappears once the report is resolved', function () {
    $scenario = accessScenario();
    $parcel = Parcel::factory()->for($scenario['occupant'])->create();
    ParcelReport::factory()->resolved()->create(['parcel_id' => $parcel->id]);
    // Laporan yang belum ditautkan ke paket mana pun juga tidak memberi tanda.
    ParcelReport::factory()->create();

    $this->actingAs($scenario['occupant'])
        ->get(route('parcels.index'))
        ->assertInertia(fn (Assert $page) => $page->where('parcels.0.misdelivered', false));
});

// Sisi pemilik kost.

test('the owner sees every report with who is holding the parcel, open ones first', function () {
    $holder = TenancyOccupant::factory()->create(['nickname' => 'Citra']);
    $resolved = ParcelReport::factory()->for($holder->user, 'reporter')->resolved()->create(['created_at' => now()->subHour()]);
    $open = ParcelReport::factory()->for($holder->user, 'reporter')->create([
        'description' => 'Paket resi JNE123.',
        'created_at' => now()->subDay(),
    ]);

    $response = $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.parcels.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('reports.0', fn (Assert $item) => $item
                ->where('id', $open->id)
                ->where('description', 'Paket resi JNE123.')
                ->where('reportedAt', $open->created_at->toDateString())
                ->where('status', 'open')
                ->where('reporter', 'Citra')
                ->where('reporterRoom', $holder->tenancy->room->number)
                ->where('parcelId', null)
                ->where('suggestedParcelId', null),
            ),
        );

    expect(array_column($response->inertiaProps('reports'), 'id'))->toBe([$open->id, $resolved->id]);
});

test('the owner gets a suggested parcel when its tracking number is in the report', function () {
    $reporter = TenancyOccupant::factory()->create()->user;
    $other = TenancyOccupant::factory()->create()->user;
    $match = Parcel::factory()->for($other)->create(['tracking_number' => 'JNE1234', 'expected_date' => '2026-11-20']);
    // Resi pendek yang kebetulan terkandung di resi lain tidak boleh menang, walaupun di daftar
    // paket ia muncul lebih dulu (perkiraan tibanya lebih dekat).
    Parcel::factory()->for($other)->create(['tracking_number' => 'JNE12', 'expected_date' => '2026-11-10']);
    // Paket yang sudah diterima dan paket milik pelapor sendiri tidak disarankan, walaupun resinya lebih panjang.
    Parcel::factory()->for($other)->receivedDirectly()->create(['tracking_number' => 'JNE1234X']);
    Parcel::factory()->for($reporter)->create(['tracking_number' => 'SPX123456789']);
    ParcelReport::factory()->for($reporter, 'reporter')->create(['description' => 'Resi jne 1234 x, juga spx 123456789']);

    $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.parcels.index'))
        ->assertInertia(fn (Assert $page) => $page->where('reports.0.suggestedParcelId', $match->id));
});

test('the owner matches a report to a parcel that has not been received yet', function () {
    $report = ParcelReport::factory()->create();
    $parcel = Parcel::factory()->atOwner()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.parcel-reports.link', $report), ['parcel_id' => $parcel->id])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.parcels.index'));

    expect($report->fresh()->parcel_id)->toBe($parcel->id);
});

test('a report cannot be matched to a parcel that was already received or does not exist', function (string $case) {
    $report = ParcelReport::factory()->create();
    $parcelId = $case === 'sudah diterima' ? Parcel::factory()->pickedUp()->create()->id : 999;

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.parcel-reports.link', $report), ['parcel_id' => $parcelId])
        ->assertInvalid('parcel_id');

    expect($report->fresh()->parcel_id)->toBeNull();
})->with(['sudah diterima', 'tidak ada']);

test('the owner resolves a report, and a resolved report is closed', function () {
    $report = ParcelReport::factory()->create();
    $owner = User::factory()->owner()->create();

    $this->actingAs($owner)
        ->put(route('owner.parcel-reports.resolve', $report))
        ->assertRedirect(route('owner.parcels.index'));

    expect($report->fresh()->resolved_at)->not->toBeNull();

    $this->actingAs($owner)->put(route('owner.parcel-reports.resolve', $report))->assertForbidden();
    $this->actingAs($owner)
        ->put(route('owner.parcel-reports.link', $report), ['parcel_id' => Parcel::factory()->create()->id])
        ->assertForbidden();
});
