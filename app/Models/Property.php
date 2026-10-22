<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property string $address
 * @property string $description
 * @property string $rules
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
// `owner_id` sengaja tidak fillable: kost selalu dibuat lewat relasi pemiliknya.
#[Fillable(['name', 'address', 'description', 'rules'])]
class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Room, $this>
     */
    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
