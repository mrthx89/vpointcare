<?php

namespace App\Models\Ai;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class DraftPengetahuan extends Model
{
    use UsesSqlServerUuid;

    public const STATUS_DRAFT = 'Draft';
    public const STATUS_NEEDS_REVISION = 'PerluRevisi';
    public const STATUS_APPROVED = 'Disetujui';
    public const STATUS_REJECTED = 'Ditolak';
    public const STATUS_ARCHIVED = 'Diarsipkan';

    protected $table = 'TAiDraftPengetahuan';

    protected $guarded = ['Id'];

    protected $casts = [
        'ConfidenceScore' => 'decimal:2',
        'DibuatOlehAi' => 'boolean',
        'TglReview' => 'datetime',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->TglBuat ??= now();
            $model->StatusReview ??= self::STATUS_DRAFT;
        });

        static::updating(function (self $model): void {
            $model->TglEdit = now();
        });
    }

    public function canApprove(): bool
    {
        return in_array($this->StatusReview, [self::STATUS_DRAFT, self::STATUS_NEEDS_REVISION], true);
    }
}
