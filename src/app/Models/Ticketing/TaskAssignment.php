<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class TaskAssignment extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'TTaskDPenugasan';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected $casts = ['TglPenugasan' => 'datetime'];
}
