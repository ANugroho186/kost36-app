<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\MaintenanceLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $property_id
 * @property int|null $room_id Kosong berarti area umum.
 * @property int|null $complaint_id
 * @property CarbonImmutable $performed_at
 * @property string $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['room_id', 'complaint_id', 'performed_at', 'description'])]
class MaintenanceLog extends Model
{
    /** @use HasFactory<MaintenanceLogFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'performed_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<Complaint, $this>
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }
}
