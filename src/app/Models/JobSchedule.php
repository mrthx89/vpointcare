<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobSchedule extends Model
{
    protected $fillable = [
        'name',
        'command',
        'cron_expression',
        'is_active',
        'description',
    ];
}
