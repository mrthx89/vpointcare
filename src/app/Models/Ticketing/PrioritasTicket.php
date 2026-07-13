<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class PrioritasTicket extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'MPrioritasTicket';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected $casts = ['NonAktif' => 'boolean'];
}
