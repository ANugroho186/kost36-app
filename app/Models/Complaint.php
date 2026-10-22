<?php

namespace App\Models;

use App\Enums\ComplaintStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ComplaintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $room_id
 * @property int|null $reporter_id
 * @property string $title
 * @property string $description
 * @property ComplaintStatus $status
 * @property CarbonImmutable|null $resolved_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
// Pelapor dan status diatur oleh alur keluhan, bukan dari input form.
#[Fillable(['title', 'description'])]
class Complaint extends Model
{
    /** @use HasFactory<ComplaintFactory> */
    use HasFactory;

    /**
     * Keluhan baru selalu berstatus dilaporkan. Kolom status tidak punya default di database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ComplaintStatus::Reported->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ComplaintStatus::class,
            'resolved_at' => 'datetime',
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
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * @return HasMany<ComplaintPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(ComplaintPhoto::class);
    }

    /**
     * @return HasMany<MaintenanceLog, $this>
     */
    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }
}
