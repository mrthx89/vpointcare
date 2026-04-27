<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MCustomer';

    protected $guarded = ['Id'];

    protected $casts = [
        'NonAktif' => 'boolean',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
        'TglSinkronTerakhir' => 'datetime',
    ];

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class, 'IdInstansi', 'Id');
    }

    public function nomorWhatsapp(): HasMany
    {
        return $this->hasMany(NomorWhatsapp::class, 'IdCustomer', 'Id');
    }
}
