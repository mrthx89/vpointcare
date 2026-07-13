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
                self::notifyAssignee($m);
            }
        });
        static::updating(function (self $m): void {
            $m->TglEdit = now();
            $m->DieditOleh = Auth::id();
            if ($m->isDirty('DitugaskanKepada')) {
                $m->TglDitugaskan = $m->DitugaskanKepada ? now() : null;
            }
            if ($m->isDirty('IdStatusTask')) {
                $final = DB::table('MStatusTask')->where('Id', $m->IdStatusTask)->value('StatusFinal');
                $m->TglDitutup = $final ? now() : null;
                $m->DitutupOleh = $final ? Auth::id() : null;
                $m->TglSelesai = $final ? ($m->TglSelesai ?: now()) : null;
            }
        });
        static::updated(function (self $m): void {
            if ($m->wasChanged('DitugaskanKepada') && $m->DitugaskanKepada) {
                DB::table('TTaskDPenugasan')->insert(['Id' => (string) str()->orderedUuid(), 'IdTask' => $m->Id, 'DitugaskanDari' => $m->getOriginal('DitugaskanKepada'), 'DitugaskanKepada' => $m->DitugaskanKepada, 'TglPenugasan' => now(), 'TglBuat' => now(), 'DibuatOleh' => Auth::id()]);
                self::notifyAssignee($m);
            }
        });
    }

    public static function nextNumber(): string
    {
        $date = now();
        $key = 'TSK-'.$date->format('Ymd');
        $sequence = DB::transaction(function () use ($key): int {
            if (DB::getDriverName() === 'sqlsrv') {
                DB::statement("EXEC sp_getapplock @Resource = ?, @LockMode = 'Exclusive', @LockOwner = 'Transaction', @LockTimeout = 10000", [$key]);
            }
            DB::table('MNomorDokumen')->updateOrInsert(['Kode' => $key], ['TglEdit' => now()]);
            $counter = DB::table('MNomorDokumen')->where('Kode', $key)->lockForUpdate()->first();
            $next = ((int) $counter->Nilai) + 1;
            DB::table('MNomorDokumen')->where('Kode', $key)->update(['Nilai' => $next, 'TglEdit' => now()]);

            return $next;
        }, 3);

        return TicketTaskSupport::number('TSK', $date, $sequence);
    }

    private static function notifyAssignee(self $task): void
    {
        $user = Pengguna::find($task->DitugaskanKepada);
        if ($user && in_array(AccessPermissions::TASK_VIEW, $user->permissionCodes(), true)) {
            $user->notify(new TaskAssignedNotification($task));
        }
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

    public function assignments(): HasMany
    {
        return $this->hasMany(TaskAssignment::class, 'IdTask', 'Id')->orderByDesc('TglPenugasan');
    }
}
