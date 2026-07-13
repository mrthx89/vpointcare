<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class StatusTicket extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MStatusTicket';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected $casts = ['StatusFinal' => 'boolean', 'NonAktif' => 'boolean'];
}
