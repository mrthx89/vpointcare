<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use App\Models\Master\Pengguna;
use App\Notifications\TaskAssignedNotification;
use App\Services\Ticketing\TicketTaskSupport;
use App\Support\AccessPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Task extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'TTask';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected $casts = ['TglDitugaskan' => 'datetime', 'TglTargetSelesai' => 'datetime', 'TglSelesai' => 'datetime', 'TglDitutup' => 'datetime', 'TglBuat' => 'datetime', 'TglEdit' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (self $m): void {
            $m->NomorTask ??= self::nextNumber();
            $m->TglBuat ??= now();
            $m->DibuatOleh ??= Auth::id();
            if ($m->DitugaskanKepada) {
                $m->TglDitugaskan ??= now();
            }
        });
        static::created(function (self $m): void {
            if ($m->DitugaskanKepada) {
                DB::table('TTaskDPenugasan')->insert(['Id' => (string) str()->orderedUuid(), 'IdTask' => $m->Id, 'DitugaskanDari' => null, 'DitugaskanKepada' => $m->DitugaskanKepada, 'TglPenugasan' => now(), 'TglBuat' => now(), 'DibuatOleh' => Auth::id()]);
            }
        });
        static::updating(function (self $m): void {
            $m->TglEdit = now();
            $m->DieditOleh = Auth::id();
            if ($m->isDirty('DitugaskanKepada')) {
                $m->TglDitugaskan = $m->DitugaskanKepada ? now() : null;
            }
        });
        static::updated(function (self $m): void {
            if ($m->wasChanged('DitugaskanKepada') && $m->DitugaskanKepada) {
                DB::table('TTaskDPenugasan')->insert(['Id' => (string) str()->orderedUuid(), 'IdTask' => $m->Id, 'DitugaskanDari' => $m->getOriginal('DitugaskanKepada'), 'DitugaskanKepada' => $m->DitugaskanKepada, 'TglPenugasan' => now(), 'TglBuat' => now(), 'DibuatOleh' => Auth::id()]);
                $user = Pengguna::find($m->DitugaskanKepada);
                if ($user && in_array(AccessPermissions::TASK_VIEW, $user->permissionCodes(), true)) {
                    $user->notify(new TaskAssignedNotification($m));
                }
            }
        });
    }

    public static function nextNumber(): string
    {
        $date = now();
        $prefix = 'TSK-'.$date->format('Ymd').'-';
        $last = (string) DB::table('TTask')->where('NomorTask', 'like', $prefix.'%')->max('NomorTask');

        return TicketTaskSupport::number('TSK', $date, $last ? ((int) substr($last, -3)) + 1 : 1);
    }

    public function checklist(): HasMany
    {
        return $this->hasMany(TaskChecklist::class, 'IdTask', 'Id')->orderBy('Urutan');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class, 'IdTask', 'Id')->orderBy('TglKomentar');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class, 'IdTask', 'Id');
    }
}
