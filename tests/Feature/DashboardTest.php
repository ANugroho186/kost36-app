<?php

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('the owner sees a summary of the property', function () {
    $this->seed(DemoSeeder::class);

    $this->actingAs(User::firstWhere('username', 'pemilik'))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('owner/dashboard')
            ->where('rooms', ['total' => 4, 'occupied' => 3])
            ->where('pendingPaymentProofs', 1)
            ->where('openComplaints', 0)
            ->where('parcels', ['waiting' => 1, 'atOwner' => 1])
            ->where('newRentalInquiries', 1),
        );
});

test('a tenant sees their own room and roommate', function () {
    $this->seed(DemoSeeder::class);

    // Tanpa ->etc(): hanya kunci ini yang boleh dikirim ke penghuni.
    $this->actingAs(User::firstWhere('username', 'penghuni1'))
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tenant/dashboard')
            ->has('tenancy', fn (Assert $tenancy) => $tenancy
                ->has('id')
                ->where('roomNumber', '1')
                ->where('status', 'active')
                ->has('paidUntil')
                ->where('vacateBy', null)
                ->where('roommates', ['Andi'])
                ->where('acceptsRoommate', false)
                ->where('canToggleRoommate', false),
            ),
        );
});

test('a tenant of an inactive tenancy sees the vacate date', function () {
    $this->seed(DemoSeeder::class);

    $this->actingAs(User::firstWhere('username', 'penghuni4'))
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('tenant/dashboard')
            ->where('tenancy.status', 'inactive')
            ->where('tenancy.vacateBy', now()->addDays(5)->toDateString())
            ->where('tenancy.roommates', ['Eko']),
        );
});

test('a tenant without a room sees an empty state', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('tenant/dashboard')
            ->where('tenancy', null),
        );
});
