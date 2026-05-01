<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class HariLibur extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MHariLibur';

    protected $guarded = ['Id'];

    protected $casts = [
        'TanggalLibur' => 'date',
        'BerlakuTahunan' => 'boolean',
        'NonAktif' => 'boolean',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
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
