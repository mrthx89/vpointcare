<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instansi extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MInstansi';

    protected $guarded = ['Id'];

    protected $casts = [
        'NonAktif' => 'boolean',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
        'TglSinkronTerakhir' => 'datetime',
    ];

    public function kontak(): HasMany
    {
        return $this->hasMany(Customer::class, 'IdInstansi', 'Id');
    }

    public function nomorWhatsapp(): HasMany
    {
        return $this->hasMany(NomorWhatsapp::class, 'IdInstansi', 'Id');
    }

    public function grupWhatsapp(): HasMany
    {
        return $this->hasMany(GrupWhatsapp::class, 'IdInstansi', 'Id');
    }
}
