<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\ComplaintPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $complaint_id
 * @property string $path
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['path'])]
// Path di disk privat. Foto dibuka lewat route yang mengecek Policy.
#[Hidden(['path'])]
class ComplaintPhoto extends Model
{
    /** @use HasFactory<ComplaintPhotoFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Complaint, $this>
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }
}
