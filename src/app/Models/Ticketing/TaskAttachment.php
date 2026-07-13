<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TaskAttachment extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'TTaskDLampiran';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected static function booted(): void
    {
        static::deleting(fn (self $m) => Storage::disk('attachments')->delete($m->PathFile));
    }
}
