<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NomorWhatsapp extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MNomorWhatsapp';

    protected $guarded = ['Id'];

    protected $casts = [
        'NomorUtama' => 'boolean',
        'Terverifikasi' => 'boolean',
        'NonAktif' => 'boolean',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'IdCustomer', 'Id');
    }

    public function instansi(): BelongsTo
    {
        return $this->belongsTo(Instansi::class, 'IdInstansi', 'Id');
    }

    public function anggotaGrupWhatsapp(): HasMany
    {
        return $this->hasMany(AnggotaGrupWhatsapp::class, 'IdNomorWhatsapp', 'Id');
    }
}
