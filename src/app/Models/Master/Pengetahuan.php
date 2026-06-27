<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class Pengetahuan extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MPengetahuan';

    protected $guarded = ['Id'];

    protected $casts = [
        'NonAktif' => 'boolean',
        'PrioritasAi' => 'integer',
        'JumlahDipakaiAi' => 'integer',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
        'TerakhirDipakaiAi' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->TglBuat ??= now();
        });

        static::updating(function (self $model): void {
            $model->TglEdit = now();
        });
    }
}

