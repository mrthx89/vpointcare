<?php

namespace App\Models;

use App\Models\Concerns\UsesSqlServerUuid;
use App\Models\Master\Pengguna;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotMessage extends Model
{
    use UsesSqlServerUuid;

    public const PERAN_USER = 'user';

    public const PERAN_ASSISTANT = 'assistant';

    protected $table = 'TChatbotInternal';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected $casts = [
        'KonteksJson' => 'array',
        'TglBuat' => 'datetime',
    ];

    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(Pengguna::class, 'IdPengguna', 'Id');
    }
}
