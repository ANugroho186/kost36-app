<?php

namespace App\Models;

use App\Enums\RentalInquiryStatus;
use Carbon\CarbonImmutable;
use Database\Factories\RentalInquiryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $property_id
 * @property string $name
 * @property string $contact
 * @property CarbonImmutable|null $preferred_date
 * @property string|null $message
 * @property RentalInquiryStatus $status
 * @property CarbonImmutable|null $done_at Dasar penghapusan otomatis (30 hari).
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
// Diisi dari form publik: hanya isian calon penyewa yang fillable, bukan status.
#[Fillable(['name', 'contact', 'preferred_date', 'message'])]
class RentalInquiry extends Model
{
    /** @use HasFactory<RentalInquiryFactory> */
    use HasFactory;

    /**
     * Permintaan baru selalu berstatus baru. Kolom status tidak punya default di database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => RentalInquiryStatus::New->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'status' => RentalInquiryStatus::class,
            'done_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
