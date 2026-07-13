<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class KategoriTicket extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MKategoriTicket';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected $casts = ['NonAktif' => 'boolean'];
}
