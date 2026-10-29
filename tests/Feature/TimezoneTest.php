<?php

use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Testing\AssertableInertia as Assert;

// 1 Nov 2026 pukul 02.00 WIB. Di UTC, saat itu masih 31 Okt pukul 19.00.
beforeEach(fn () => $this->travelTo(CarbonImmutable::parse('2026-10-31 19:00:00', 'UTC')));

test('today follows the time in Jakarta', function () {
    expect(today()->toDateString())->toBe('2026-11-01')
        ->and(now()->format('H:i'))->toBe('02:00');
});

test('forms default to the date in Jakarta, even right after midnight', function () {
    $this->actingAs(User::factory()->owner()->create())
        ->get(route('owner.tenancies.index'))
        ->assertInertia(fn (Assert $page) => $page->where('today', '2026-11-01'));
});
