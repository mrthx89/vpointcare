<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class TicketActivity extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'TTicketD';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected $casts = ['TglAktivitas' => 'datetime', 'TglBuat' => 'datetime'];
}
