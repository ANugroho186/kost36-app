<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Owner\CreateOccupantAccountRequest;
use App\Http\Requests\Owner\ResetOccupantPasswordRequest;
use App\Models\Tenancy;
use App\Models\TenancyOccupant;
use App\Models\User;
use App\Support\TemporaryPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

/**
 * Akun login penghuni dibuat dan direset oleh pemilik. Route-nya memakai scopeBindings(),
 * jadi {occupant} harus penghuni dari {tenancy} di URL.
 *
 * Password sementara hanya dikirim sekali lewat flash dan tidak pernah disimpan dalam bentuk asli.
 */
class OccupantAccountController extends Controller
{
    public function store(CreateOccupantAccountRequest $request, Tenancy $tenancy, TenancyOccupant $occupant): RedirectResponse
    {
        if ($occupant->user_id !== null || $occupant->moved_out_at !== null) {
            throw ValidationException::withMessages([
                'username' => __('This occupant already has an account or has moved out.'),
            ]);
        }

        [$account, $password] = DB::transaction(function () use ($request, $tenancy, $occupant): array {
            $account = User::create([
                'name' => $occupant->nickname ?? $request->string('username')->toString(),
                'username' => $request->string('username')->toString(),
                'email' => $request->filled('email') ? $request->string('email')->toString() : null,
                // Sementara, langsung diganti di bawah: format password butuh ID akun yang baru dibuat.
                'password' => Str::password(32),
            ]);

            $password = $this->resetToTemporaryPassword($account, $tenancy, $occupant);
            $occupant->user()->associate($account)->save();

            return [$account, $password];
        });

        $this->flashTemporaryPassword($account, $password);

        return to_route('owner.tenancies.show', $tenancy);
    }

    public function resetPassword(ResetOccupantPasswordRequest $request, Tenancy $tenancy, TenancyOccupant $occupant): RedirectResponse
    {
        $account = $occupant->user;

        if ($account === null) {
            throw ValidationException::withMessages(['owner_password' => __('This occupant has no account.')]);
        }

        $password = DB::transaction(function () use ($account, $tenancy, $occupant): string {
            $password = $this->resetToTemporaryPassword($account, $tenancy, $occupant);

            // Keluarkan penghuni dari semua perangkat yang masih login: cookie "remember me"
            // lama tidak berlaku lagi, dan sesi di tabel sessions dihapus.
            $account->forceFill(['remember_token' => Str::random(60)])->save();
            DB::table('sessions')->where('user_id', $account->id)->delete();

            return $password;
        });

        $this->flashTemporaryPassword($account, $password);

        return to_route('owner.tenancies.show', $tenancy);
    }

    private function resetToTemporaryPassword(User $account, Tenancy $tenancy, TenancyOccupant $occupant): string
    {
        $password = TemporaryPassword::generate($occupant->moved_in_at, $tenancy->room, $account);

        $account->forceFill([
            'password' => $password,
            'has_temporary_password' => true,
        ])->save();

        return $password;
    }

    private function flashTemporaryPassword(User $account, string $password): void
    {
        Inertia::flash('temporaryPassword', [
            'username' => $account->username,
            'password' => $password,
        ]);
    }
}
