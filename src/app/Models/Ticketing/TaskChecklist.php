<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class TaskChecklist extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'TTaskDChecklist';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected $casts = ['Selesai' => 'boolean', 'TglSelesai' => 'datetime'];

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            if ($m->isDirty('Selesai')) {
                $m->TglSelesai = $m->Selesai ? now() : null;
                $m->DiselesaikanOleh = $m->Selesai ? Auth::id() : null;
            }
        });
    }
}
