<?php

namespace App\Models;

use App\Enums\PaymentProofStatus;
use Carbon\CarbonImmutable;
use Database\Factories\PaymentProofFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $tenancy_id
 * @property int|null $uploaded_by
 * @property string|null $file_path
 * @property PaymentProofStatus $status
 * @property int|null $months_granted
 * @property CarbonImmutable|null $paid_until_before
 * @property CarbonImmutable|null $paid_until_after
 * @property string|null $review_note
 * @property CarbonImmutable|null $reviewed_at
 * @property CarbonImmutable|null $file_deleted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
// Status dan hasil tinjauan diubah lewat alur verifikasi, bukan dari input form.
#[Fillable(['file_path'])]
// Path penyimpanan internal. File dibuka lewat route yang mengecek Policy.
#[Hidden(['file_path'])]
class PaymentProof extends Model
{
    /** @use HasFactory<PaymentProofFactory> */
    use HasFactory;

    /**
     * Bukti baru selalu menunggu verifikasi. Kolom status tidak punya default di database.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => PaymentProofStatus::Pending->value,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentProofStatus::class,
            'months_granted' => 'integer',
            'paid_until_before' => 'date',
            'paid_until_after' => 'date',
            'reviewed_at' => 'datetime',
            'file_deleted_at' => 'datetime',
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
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
