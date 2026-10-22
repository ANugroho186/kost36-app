<?php

namespace App\Models;

use App\Enums\NotifyChannel;
use Carbon\CarbonImmutable;
use Database\Factories\TenancyOccupantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenancy_id
 * @property int|null $user_id
 * @property string|null $nickname
 * @property CarbonImmutable $moved_in_at
 * @property CarbonImmutable|null $moved_out_at
 * @property NotifyChannel|null $notify_channel
 * @property string|null $notify_contact
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
// `user_id` tidak fillable: akun hanya ditautkan lewat alur pemilik.
#[Fillable(['nickname', 'moved_in_at', 'notify_channel', 'notify_contact'])]
// Kontak tidak boleh terlihat teman sekamar, jadi tidak ikut dikirim ke frontend kecuali diminta.
#[Hidden(['notify_contact'])]
class TenancyOccupant extends Model
{
    /** @use HasFactory<TenancyOccupantFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'moved_in_at' => 'date',
            'moved_out_at' => 'date',
            'notify_channel' => NotifyChannel::class,
        ];
    }

    /**
     * @return BelongsTo<Tenancy, $this>
     */
    public function tenancy(): BelongsTo
    {
        return $this->belongsTo(Tenancy::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
