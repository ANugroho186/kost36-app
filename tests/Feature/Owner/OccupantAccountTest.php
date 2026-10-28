<?php

use App\Enums\UserRole;
use App\Models\Room;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Penghuni tanpa akun yang masuk 15 Juli 2026 di kamar 3, seperti contoh format password.
 */
function occupantWithoutAccount(): TenancyOccupant
{
    $tenancy = Tenancy::factory()->for(Room::factory()->state(['number' => '3']))->create();

    return TenancyOccupant::factory()->for($tenancy)->withoutAccount()->create([
        'nickname' => 'Eko',
        'moved_in_at' => '2026-07-15',
    ]);
}

function occupantWithAccount(): TenancyOccupant
{
    $occupant = occupantWithoutAccount();
    $occupant->user()->associate(User::factory()->create(['username' => 'eko']))->save();

    return $occupant;
}

function flashedTemporaryPassword(): string
{
    return session('inertia.flash_data.temporaryPassword.password');
}

test('the owner creates a login with a temporary password in the agreed format', function () {
    $occupant = occupantWithoutAccount();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.tenancies.occupants.account.store', [$occupant->tenancy, $occupant]), [
            'username' => 'Eko',
            'email' => '',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('owner.tenancies.show', $occupant->tenancy))
        ->assertInertiaFlash('temporaryPassword.username', 'eko');

    $account = $occupant->fresh()->user;
    $password = flashedTemporaryPassword();

    // bulan-tanggal-kamar-idAkun-KODE-tahun. KODE: 6 karakter tanpa I, L, O, 0, dan 1.
    expect($password)->toMatch('/^07-15-3-'.$account->id.'-[A-HJKMNP-Z2-9]{6}-2026$/')
        ->and(Hash::check($password, $account->password))->toBeTrue()
        ->and($account->username)->toBe('eko')
        ->and($account->name)->toBe('Eko')
        ->and($account->email)->toBeNull()
        ->and($account->role)->toBe(UserRole::Tenant)
        ->and($account->has_temporary_password)->toBeTrue();
});

test('the new tenant can log in with the temporary password', function () {
    $occupant = occupantWithoutAccount();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.tenancies.occupants.account.store', [$occupant->tenancy, $occupant]), ['username' => 'eko']);
    $password = flashedTemporaryPassword();

    $this->post(route('logout'));
    $this->post(route('login.store'), ['username' => 'eko', 'password' => $password]);

    $this->assertAuthenticatedAs($occupant->fresh()->user);
});

test('usernames are compared in lowercase and must be unique', function () {
    User::factory()->create(['username' => 'eko']);
    $occupant = occupantWithoutAccount();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.tenancies.show', $occupant->tenancy))
        ->post(route('owner.tenancies.occupants.account.store', [$occupant->tenancy, $occupant]), ['username' => 'EKO'])
        ->assertSessionHasErrors('username');

    expect($occupant->fresh()->user_id)->toBeNull();
});

test('usernames only allow simple characters', function (string $username) {
    $occupant = occupantWithoutAccount();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.tenancies.show', $occupant->tenancy))
        ->post(route('owner.tenancies.occupants.account.store', [$occupant->tenancy, $occupant]), ['username' => $username])
        ->assertSessionHasErrors('username');
})->with(['eko santoso', 'eko@kost', 'ek']);

test('an occupant can only get one account', function () {
    $owner = User::factory()->owner()->create();
    $occupant = occupantWithAccount();
    $accounts = User::count();

    $this->actingAs($owner)
        ->from(route('owner.tenancies.show', $occupant->tenancy))
        ->post(route('owner.tenancies.occupants.account.store', [$occupant->tenancy, $occupant]), ['username' => 'eko-baru'])
        ->assertSessionHasErrors('username');

    expect(User::count())->toBe($accounts);
});

test('an occupant cannot be reached through another tenancy in the URL', function () {
    $occupant = occupantWithoutAccount();
    $anotherTenancy = Tenancy::factory()->create();

    $this->actingAs(User::factory()->owner()->create())
        ->post(route('owner.tenancies.occupants.account.store', [$anotherTenancy, $occupant]), ['username' => 'eko'])
        ->assertNotFound();
});

test('the owner resets a tenant password and logs them out everywhere', function () {
    $occupant = occupantWithAccount();
    $account = $occupant->user;
    $oldRememberToken = $account->remember_token;
    DB::table('sessions')->insert([
        'id' => 'sesi-hp-eko',
        'user_id' => $account->id,
        'payload' => '',
        'last_activity' => time(),
    ]);

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.tenancies.occupants.password.reset', [$occupant->tenancy, $occupant]), [
            'owner_password' => 'password',
        ])
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('temporaryPassword.username', 'eko');

    $password = flashedTemporaryPassword();
    $account->refresh();

    expect(Hash::check($password, $account->password))->toBeTrue()
        ->and(Hash::check('password', $account->password))->toBeFalse()
        ->and($account->has_temporary_password)->toBeTrue()
        ->and($account->remember_token)->not->toBe($oldRememberToken)
        ->and(DB::table('sessions')->where('user_id', $account->id)->exists())->toBeFalse();
});

test('a wrong owner password leaves the tenant password unchanged', function () {
    $occupant = occupantWithAccount();

    $this->actingAs(User::factory()->owner()->create())
        ->from(route('owner.tenancies.show', $occupant->tenancy))
        ->put(route('owner.tenancies.occupants.password.reset', [$occupant->tenancy, $occupant]), [
            'owner_password' => 'salah',
        ])
        ->assertSessionHasErrors('owner_password')
        ->assertInertiaFlashMissing('temporaryPassword');

    expect(Hash::check('password', $occupant->user->fresh()->password))->toBeTrue();
});

test('owner accounts cannot be reset through a tenancy', function () {
    $occupant = occupantWithoutAccount();
    $occupant->user()->associate(User::factory()->owner()->create())->save();

    $this->actingAs(User::factory()->owner()->create())
        ->put(route('owner.tenancies.occupants.password.reset', [$occupant->tenancy, $occupant]), [
            'owner_password' => 'password',
        ])
        ->assertForbidden();
});

test('choosing a new password in settings removes the reminder', function () {
    $user = User::factory()->create(['has_temporary_password' => true]);

    $this->actingAs($user)
        ->from(route('security.edit'))
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors();

    expect($user->refresh()->has_temporary_password)->toBeFalse();
});

test('resetting the password by email also removes the reminder', function () {
    $user = User::factory()->create(['has_temporary_password' => true]);

    $this->post(route('password.update'), [
        'token' => Password::createToken($user),
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors();

    expect($user->refresh()->has_temporary_password)->toBeFalse();
});

test('the tenant dashboard shows the reminder only for a temporary password', function (bool $temporary) {
    $tenant = TenancyOccupant::factory()->create()->user;
    $tenant->forceFill(['has_temporary_password' => $temporary])->save();

    $this->actingAs($tenant)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page->where('hasTemporaryPassword', $temporary));
})->with([
    'masih password sementara' => [true],
    'sudah diganti' => [false],
]);
