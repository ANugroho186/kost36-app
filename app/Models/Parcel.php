<?php

namespace App\Models;

use App\Enums\ParcelStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ParcelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $tracking_number
 * @property string|null $item_name Terenkripsi, hanya untuk penghuni pemilik paket.
 * @property CarbonImmutable|null $expected_date
 * @property CarbonImmutable|null $held_by_owner_at
 * @property CarbonImmutable|null $collected_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read ParcelStatus $status
 */
// Hanya isian penghuni yang fillable. Pemilik paket dan waktu serah terima diatur lewat alur paket.
#[Fillable(['tracking_number', 'item_name', 'expected_date'])]
// Nama barang tidak pernah ikut dikirim ke frontend, kecuali halaman pemilik paket memintanya dengan makeVisible().
#[Hidden(['item_name'])]
class Parcel extends Model
{
    /** @use HasFactory<ParcelFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_name' => 'encrypted',
            'expected_date' => 'date',
            'held_by_owner_at' => 'datetime',
            'collected_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<ParcelStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): ParcelStatus => $this->statusFromHandoverTimes());
    }

    private function statusFromHandoverTimes(): ParcelStatus
    {
        return match (true) {
            $this->collected_at !== null && $this->held_by_owner_at !== null => ParcelStatus::PickedUp,
            $this->collected_at !== null => ParcelStatus::ReceivedDirectly,
            $this->held_by_owner_at !== null => ParcelStatus::AtOwner,
            default => ParcelStatus::Waiting,
        };
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ParcelReport, $this>
     */
    public function reports(): HasMany
    {
        return $this->hasMany(ParcelReport::class);
    }
}
