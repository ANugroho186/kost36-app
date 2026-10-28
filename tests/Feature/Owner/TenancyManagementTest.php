<?php

use App\Models\Room;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

function startTenancyData(Room $room, array $overrides = []): array
{
    return [
        'room_id' => $room->id,
        'start_date' => '2026-01-01',
        'months_paid' => 1,
        'nickname' => 'Budi',
        ...$overrides,
    ];
}

test('the owner starts a tenancy for an empty room', function () {
    $room = Room::factory()->create();

    $response = $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.tenancies.store'), startTenancyData($room))
        ->assertSessionHasNoErrors();

    $tenancy = Tenancy::sole();
    $response->assertRedirect(route('owner.tenancies.show', $tenancy));

    expect($tenancy->room_id)->toBe($room->id)
        ->and($tenancy->accepts_roommate)->toBeFalse()
        ->and($tenancy->activeOccupants()->pluck('nickname')->all())->toBe(['Budi'])
        ->and($tenancy->activeOccupants()->sole()->moved_in_at->toDateString())->toBe('2026-01-01');
});

test('the next payment date follows the months paid upfront', function (string $start, int $months, string $expected) {
    $room = Room::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.tenancies.store'), startTenancyData($room, ['start_date' => $start, 'months_paid' => $months]));

    expect(Tenancy::sole()->paid_until->toDateString())->toBe($expected);
})->with([
    '1 bulan' => ['2026-01-01', 1, '2026-02-01'],
    '3 bulan' => ['2026-01-01', 3, '2026-04-01'],
    '12 bulan' => ['2026-01-01', 12, '2027-01-01'],
    // NoOverflow: 31 Jan + 1 bulan = 28 Feb, bukan 3 Maret.
    'akhir bulan' => ['2026-01-31', 1, '2026-02-28'],
]);

test('an occupied room cannot get a second tenancy', function () {
    // Sewa nonaktif masih menempati kamar sampai tanggal pengosongan.
    $tenancy = Tenancy::factory()->inactive()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.tenancies.create'))
        ->post(route('owner.tenancies.store'), startTenancyData($tenancy->room))
        ->assertSessionHasErrors('room_id');

    expect(Tenancy::count())->toBe(1);
});

test('two tenancies started at the same moment end in a clear message, not an error', function () {
    $room = Room::factory()->create();

    // Meniru permintaan lain yang menyimpan sewa untuk kamar yang sama tepat setelah
    // pengecekan "kamar kosong?" lolos. Partial unique index menolak sewa yang kedua.
    Tenancy::creating(function () use ($room): void {
        DB::table('tenancies')->insert([
            'room_id' => $room->id,
            'start_date' => '2026-01-01',
            'paid_until' => '2026-02-01',
            'status' => 'active',
            'accepts_roommate' => false,
        ]);
    });

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.tenancies.create'))
        ->post(route('owner.tenancies.store'), startTenancyData($room))
        ->assertRedirect(route('owner.tenancies.create'))
        ->assertSessionHasErrors('room_id');
});

test('months paid upfront must be between 1 and 12', function (int $months) {
    $room = Room::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.tenancies.create'))
        ->post(route('owner.tenancies.store'), startTenancyData($room, ['months_paid' => $months]))
        ->assertSessionHasErrors('months_paid');
})->with([0, 13]);

test('the owner sees occupied rooms and their occupants', function () {
    $this->seed(DemoSeeder::class);

    $this->actingAs(User::firstWhere('username', 'pemilik'))
        ->get(route('owner.tenancies.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('owner/tenancies/index')
            ->has('tenancies', 3)
            ->where('tenancies.0.roomNumber', '1')
            ->where('tenancies.0.occupants', ['Budi', 'Andi'])
            ->where('tenancies.2.status', 'inactive')
            ->where('availableRooms', 1),
        );
});

test('the start form only offers empty rooms', function () {
    $this->seed(DemoSeeder::class);

    $this->actingAs(User::firstWhere('username', 'pemilik'))
        ->get(route('owner.tenancies.create'))
        ->assertInertia(fn (Assert $page) => $page
            ->has('rooms', 1)
            ->where('rooms.0.number', '4'),
        );
});

test('the tenancy page lists occupants without their contact details', function () {
    $tenancy = Tenancy::factory()->create();
    TenancyOccupant::factory()->for($tenancy)->create([
        'nickname' => 'Budi',
        'notify_contact' => 'budi@example.com',
    ]);

    // Tanpa ->etc(): hanya kunci ini yang boleh dikirim untuk setiap penghuni.
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.tenancies.show', $tenancy))
        ->assertInertia(fn (Assert $page) => $page
            ->component('owner/tenancies/show')
            ->has('tenancy.occupants.0', fn (Assert $occupant) => $occupant
                ->has('id')
                ->where('nickname', 'Budi')
                ->has('movedInAt')
                ->where('hasAccount', true)
                ->has('username'),
            )
            ->where('canAddOccupant', false)
            ->where('canToggleRoommate', true),
        );
});

test('a second person can join once the room is open for sharing', function () {
    $tenancy = Tenancy::factory()->create(['accepts_roommate' => true]);
    TenancyOccupant::factory()->for($tenancy)->create();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.tenancies.occupants.store', $tenancy), [
            'nickname' => 'Andi',
            'moved_in_at' => '2026-10-28',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.tenancies.show', $tenancy));

    expect($tenancy->activeOccupants()->count())->toBe(2)
        // Izin berbagi berlaku untuk satu kali penambahan.
        ->and($tenancy->fresh()->accepts_roommate)->toBeFalse();
});

test('a second person cannot join while the room is marked as full', function () {
    $tenancy = Tenancy::factory()->create();
    TenancyOccupant::factory()->for($tenancy)->create();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.tenancies.show', $tenancy))
        ->post(route('owner.tenancies.occupants.store', $tenancy), [
            'nickname' => 'Orang Asing',
            'moved_in_at' => '2026-10-28',
        ])
        ->assertSessionHasErrors('nickname');

    expect($tenancy->activeOccupants()->count())->toBe(1);
});

test('nobody can join beyond the room capacity, even when sharing is allowed', function (int $capacity, int $occupants) {
    $tenancy = Tenancy::factory()
        ->for(Room::factory()->state(['capacity' => $capacity]))
        ->create(['accepts_roommate' => true]);
    TenancyOccupant::factory()->for($tenancy)->count($occupants)->create();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.tenancies.show', $tenancy))
        ->post(route('owner.tenancies.occupants.store', $tenancy), [
            'nickname' => 'Ketiga',
            'moved_in_at' => '2026-10-28',
        ])
        ->assertSessionHasErrors('nickname');

    expect($tenancy->activeOccupants()->count())->toBe($occupants);
})->with([
    'kamar berdua sudah berisi 2' => [2, 2],
    'kamar sendiri sudah berisi 1' => [1, 1],
]);

test('occupants cannot be added to an inactive tenancy', function () {
    $tenancy = Tenancy::factory()->inactive()->create(['accepts_roommate' => true]);
    TenancyOccupant::factory()->for($tenancy)->create();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.tenancies.occupants.store', $tenancy), [
            'nickname' => 'Andi',
            'moved_in_at' => '2026-10-28',
        ])
        ->assertForbidden();
});
