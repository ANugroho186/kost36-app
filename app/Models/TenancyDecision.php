<?php

namespace App\Models;

use App\Enums\TenancyDecisionType;
use Carbon\CarbonImmutable;
use Database\Factories\TenancyDecisionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenancy_id
 * @property TenancyDecisionType $type
 * @property int|null $days
 * @property CarbonImmutable|null $new_date
 * @property string|null $note
 * @property CarbonImmutable $created_at
 */
#[Fillable(['type', 'days', 'new_date', 'note'])]
class TenancyDecision extends Model
{
    /** @use HasFactory<TenancyDecisionFactory> */
    use HasFactory;

    // Keputusan tidak pernah diubah setelah dicatat, jadi tabelnya tanpa updated_at.
    public const UPDATED_AT = null;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TenancyDecisionType::class,
            'days' => 'integer',
            'new_date' => 'date',
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
