<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TaskComment extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'TTaskDKomentar';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            $m->TglKomentar ??= now();
            $m->TglBuat ??= now();
            $m->DibuatOleh ??= Auth::id();
        });
    }
}
