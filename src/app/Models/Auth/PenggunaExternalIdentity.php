<?php

namespace App\Models\Auth;

use App\Models\Concerns\UsesSqlServerUuid;
use App\Models\Master\Pengguna;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenggunaExternalIdentity extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MPenggunaExternalIdentity';

    protected $guarded = ['Id'];

    protected function casts(): array
    {
        return [
            'EmailTerverifikasi' => 'boolean',
            'Metadata' => 'array',
            'TglTaut' => 'datetime',
            'LoginTerakhirPada' => 'datetime',
            'TglBuat' => 'datetime',
            'TglEdit' => 'datetime',
        ];
    }

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'IdPengguna', 'Id');
    }
}
