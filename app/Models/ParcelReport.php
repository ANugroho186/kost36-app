<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ParcelReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int|null $reporter_id
 * @property int|null $parcel_id Diisi pemilik setelah mencocokkan laporan dengan paket.
 * @property string $description
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
// `parcel_id` tidak fillable: hanya pemilik yang menautkan laporan ke paket, agar pelapor tidak bisa menyentuh paket orang lain.
#[Fillable(['description'])]
class ParcelReport extends Model
{
    /** @use HasFactory<ParcelReportFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return BelongsTo<Parcel, $this>
     */
    public function parcel(): BelongsTo
    {
        return $this->belongsTo(Parcel::class);
    }
}
