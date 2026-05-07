<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrupWhatsapp extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MGrupWhatsapp';

    protected $guarded = ['Id'];

    protected $casts = [
        'NonAktif' => 'boolean',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $grup): void {
            $grup->IdGrupWaha = $grup->IdGrupWaha ? trim((string) $grup->IdGrupWaha) : null;
        });
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class, 'IdInstansi', 'Id');
    }

    public function anggota(): HasMany
    {
        return $this->hasMany(AnggotaGrupWhatsapp::class, 'IdGrupWhatsapp', 'Id');
    }
}
