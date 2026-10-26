<?php

namespace App\Models;

use App\Enums\UserRole;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $username
 * @property string|null $email
 * @property CarbonImmutable|null $email_verified_at
 * @property string $password
 * @property UserRole $role
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property CarbonImmutable|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
// `role` sengaja tidak fillable agar tidak bisa diubah lewat input form (mass assignment).
#[Fillable(['name', 'username', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Username selalu disimpan huruf kecil, karena Fortify juga mengecilkan input login
     * (`lowercase_usernames`). Tanpa ini, akun "Budi" tidak akan pernah cocok dengan "budi".
     *
     * @return Attribute<never, string>
     */
    protected function username(): Attribute
    {
        return Attribute::set(fn (string $value): string => Str::lower($value));
    }

    /**
     * Email juga disimpan huruf kecil, karena Fortify mengecilkan input di form lupa password.
     *
     * @return Attribute<never, string|null>
     */
    protected function email(): Attribute
    {
        return Attribute::set(fn (?string $value): ?string => $value === null ? null : Str::lower($value));
    }

    public function isOwner(): bool
    {
        return $this->role === UserRole::Owner;
    }

    /**
     * Data penghuni yang sedang berlaku: belum keluar, dari sewa yang masih menempati kamar.
     * Paling banyak satu, dijaga partial unique index di tenancy_occupants.
     *
     * @return HasOne<TenancyOccupant, $this>
     */
    public function currentOccupancy(): HasOne
    {
        return $this->hasOne(TenancyOccupant::class)
            ->whereNull('moved_out_at')
            ->whereHas('tenancy', fn (Builder $query) => $query->occupying());
    }

    /**
     * Apakah user ini penghuni yang sedang berlaku dari sewa tersebut.
     * Selalu query ulang, supaya tidak memakai data relasi lama yang tersimpan di model.
     */
    public function occupies(Tenancy $tenancy): bool
    {
        return $this->currentOccupancy()->where('tenancy_id', $tenancy->id)->exists();
    }

    /**
     * Kost yang dimiliki user ini (hanya untuk pemilik).
     *
     * @return HasMany<Property, $this>
     */
    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'owner_id');
    }

    /**
     * Riwayat user ini sebagai penghuni, termasuk kamar yang sudah ditinggalkan.
     *
     * @return HasMany<TenancyOccupant, $this>
     */
    public function occupancies(): HasMany
    {
        return $this->hasMany(TenancyOccupant::class);
    }

    /**
     * Paket pesanan milik user ini.
     *
     * @return HasMany<Parcel, $this>
     */
    public function parcels(): HasMany
    {
        return $this->hasMany(Parcel::class);
    }

    /**
     * Laporan paket salah terima yang dibuat user ini.
     *
     * @return HasMany<ParcelReport, $this>
     */
    public function parcelReports(): HasMany
    {
        return $this->hasMany(ParcelReport::class, 'reporter_id');
    }
}
