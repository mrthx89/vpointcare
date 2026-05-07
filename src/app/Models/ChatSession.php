<?php

namespace App\Models;

use App\Models\Concerns\UsesSqlServerUuid;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'TChat';

    protected $guarded = ['Id'];

    protected $casts = [
        'TglChatTerakhir' => 'datetime',
        'TglBuat' => 'datetime',
        'TglEdit' => 'datetime',
    ];
}
