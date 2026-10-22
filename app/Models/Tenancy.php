<?php

namespace App\Models;

use App\Enums\TenancyStatus;
use Carbon\CarbonImmutable;
use Database\Factories\TenancyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $room_id
 * @property CarbonImmutable $start_date
 * @property CarbonImmutable $paid_until Tanggal bayar berikutnya (jatuh tempo).
 * @property CarbonImmutable|null $extended_until
 * @property TenancyStatus $status
 * @property CarbonImmutable|null $deactivated_at
 * @property CarbonImmutable|null $vacate_by
 * @property CarbonImmutable|null $end_date
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
// Status dan tanggal penonaktifan diubah lewat alur bisnis, bukan dari input form.
#[Fillable(['start_date', 'paid_until'])]
class Tenancy extends Model
{
    /** @use HasFactory<TenancyFactory> */
    use HasFactory;

    /**
     * Sewa baru selalu aktif. Kolom status tidak punya default di database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TenancyStatus::Active->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'paid_until' => 'date',
            'extended_until' => 'date',
            'status' => TenancyStatus::class,
            'deactivated_at' => 'datetime',
            'vacate_by' => 'date',
            'end_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Semua penghuni, termasuk yang sudah keluar.
     *
     * @return HasMany<TenancyOccupant, $this>
     */
    public function occupants(): HasMany
    {
        return $this->hasMany(TenancyOccupant::class);
    }

    /**
     * @return HasMany<TenancyOccupant, $this>
     */
    public function activeOccupants(): HasMany
    {
        return $this->hasMany(TenancyOccupant::class)->whereNull('moved_out_at');
    }

    /**
     * @return HasMany<PaymentProof, $this>
     */
    public function paymentProofs(): HasMany
    {
        return $this->hasMany(PaymentProof::class);
    }

    /**
     * @return HasMany<TenancyDecision, $this>
     */
    public function decisions(): HasMany
    {
        return $this->hasMany(TenancyDecision::class);
    }

    /**
     * @return HasMany<ReminderLog, $this>
     */
    public function reminderLogs(): HasMany
    {
        return $this->hasMany(ReminderLog::class);
    }
}
