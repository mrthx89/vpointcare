<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class TicketAssignment extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'TTicketDPenugasan';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected $casts = ['TglPenugasan' => 'datetime'];
}
