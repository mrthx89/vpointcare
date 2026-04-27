<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnggotaGrupWhatsapp extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MAnggotaGrupWhatsapp';

    protected $guarded = ['Id'];

    protected $casts = [
        'NonAktif' => 'boolean',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $anggota): void {
            if ($anggota->IdNomorWhatsapp && ! $anggota->IdCustomer) {
                $anggota->IdCustomer = NomorWhatsapp::query()->whereKey($anggota->IdNomorWhatsapp)->value('IdCustomer');
            }
        });
    }

    public function grupWhatsapp(): BelongsTo
    {
        return $this->belongsTo(GrupWhatsapp::class, 'IdGrupWhatsapp', 'Id');
    }

    public function nomorWhatsapp(): BelongsTo
    {
        return $this->belongsTo(NomorWhatsapp::class, 'IdNomorWhatsapp', 'Id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'IdCustomer', 'Id');
    }
}
