<?php

use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

// Memastikan error berasal dari foreign key (restrictOnDelete), bukan error database lain.
// Pesan PostgreSQL dan SQLite berbeda, tapi keduanya memuat "foreign key".
expect()->extend('toViolateForeignKey', function () {
    return $this->toThrow(
        fn (QueryException $e) => expect($e->getMessage())->toMatch('/foreign key/i'),
    );
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Skenario hak akses: satu sewa berisi dua penghuni (teman sekamar), satu penghuni yang
 * sudah keluar dari sewa itu, dan satu penghuni kamar lain.
 *
 * @return array{owner: User, tenancy: Tenancy, occupant: User, roommate: User, formerOccupant: User, otherTenant: User}
 */
function accessScenario(): array
{
    $tenancy = Tenancy::factory()->create();

    return [
        'owner' => User::factory()->owner()->create(),
        'tenancy' => $tenancy,
        'occupant' => TenancyOccupant::factory()->for($tenancy)->create()->user,
        'roommate' => TenancyOccupant::factory()->for($tenancy)->create()->user,
        'formerOccupant' => TenancyOccupant::factory()->for($tenancy)->movedOut()->create()->user,
        'otherTenant' => TenancyOccupant::factory()->create()->user,
    ];
}
