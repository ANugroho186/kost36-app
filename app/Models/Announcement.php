<?php

namespace App\Models;

use App\Enums\AnnouncementStatus;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $property_id
 * @property string $title
 * @property string $body
 * @property CarbonImmutable $published_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read AnnouncementStatus $status
 */
// property_id tidak fillable: pengumuman selalu dibuat lewat relasi kost-nya.
#[Fillable(['title', 'body', 'published_at', 'expires_at'])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Terjadwal (belum tayang), tayang, atau kedaluwarsa, dihitung terhadap waktu sekarang.
     * Tepat pada published_at sudah tayang, dan tepat pada expires_at sudah kedaluwarsa.
     *
     * @return Attribute<AnnouncementStatus, never>
     */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): AnnouncementStatus => $this->statusAt(now()));
    }

    private function statusAt(CarbonInterface $moment): AnnouncementStatus
    {
        return match (true) {
            $this->published_at->isAfter($moment) => AnnouncementStatus::Scheduled,
            $this->expires_at !== null && ! $this->expires_at->isAfter($moment) => AnnouncementStatus::Expired,
            default => AnnouncementStatus::Live,
        };
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }
}
