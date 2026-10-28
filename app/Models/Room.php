<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $property_id
 * @property string $number
 * @property int $capacity
 * @property string $facilities
 * @property bool $is_published
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['number', 'capacity', 'facilities', 'is_published'])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Jumlah penghuni yang sedang tinggal (dari sewa active/inactive), 0 kalau kamar kosong.
     */
    public function activeOccupantCount(): int
    {
        return $this->tenancies()->occupying()->first()?->activeOccupants()->count() ?? 0;
    }

    /**
     * Kamar dengan data terkait tidak bisa dihapus (restrictOnDelete), jadi dicek lebih dulu
     * agar pemilik mendapat pesan yang jelas, bukan error database.
     */
    public function hasRelatedRecords(): bool
    {
        return $this->tenancies()->exists()
            || $this->complaints()->exists()
            || $this->maintenanceLogs()->exists()
            || $this->inventoryItems()->exists();
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return HasMany<RoomPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(RoomPhoto::class)->orderBy('sort_order');
    }

    /**
     * Semua sewa kamar ini, termasuk riwayat yang sudah berakhir.
     *
     * @return HasMany<Tenancy, $this>
     */
    public function tenancies(): HasMany
    {
        return $this->hasMany(Tenancy::class);
    }

    /**
     * @return HasMany<Complaint, $this>
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * @return HasMany<MaintenanceLog, $this>
     */
    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }

    /**
     * @return HasMany<InventoryItem, $this>
     */
    public function inventoryItems(): HasMany
    {
        return $this->hasMany(InventoryItem::class);
    }
}
