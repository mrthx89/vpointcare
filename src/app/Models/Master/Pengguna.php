<?php

namespace App\Models\Master;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class Pengguna extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MPengguna';

    protected $guarded = ['Id'];

    protected $hidden = [
        'Password',
        'RememberToken',
    ];

    protected $casts = [
        'NonAktif' => 'boolean',
        'EmailTerverifikasiPada' => 'datetime',
        'LoginTerakhirPada' => 'datetime',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
    ];
}
