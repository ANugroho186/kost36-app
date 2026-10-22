<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ReminderLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenancy_id
 * @property CarbonImmutable $due_date
 * @property string $type Dijadikan enum di Fase 3, setelah daftar tipe pengingat diputuskan.
 * @property CarbonImmutable $sent_at
 */
#[Fillable(['due_date', 'type', 'sent_at'])]
class ReminderLog extends Model
{
    /** @use HasFactory<ReminderLogFactory> */
    use HasFactory;

    /**
     * Waktu kejadian sudah dicatat di sent_at, jadi tabelnya tanpa created_at/updated_at.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Tenancy, $this>
     */
    public function tenancy(): BelongsTo
    {
        return $this->belongsTo(Tenancy::class);
    }
}
