<?php

namespace App\Models\Ticketing;

use App\Models\Concerns\UsesSqlServerUuid;
use App\Models\Master\Pengguna;
use App\Notifications\TicketAssignedNotification;
use App\Services\Ticketing\TicketTaskSupport;
use App\Support\AccessPermissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Ticket extends Model
{
    use UsesSqlServerUuid;

    protected $table = 'TTicket';

    protected $guarded = ['Id'];

    public $timestamps = false;

    protected $casts = ['TglDitugaskan' => 'datetime', 'TglTargetSelesai' => 'datetime', 'TglSelesai' => 'datetime', 'TglDitutup' => 'datetime', 'TglBuat' => 'datetime', 'TglEdit' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (self $m): void {
            $m->NomorTicket ??= self::nextNumber();
            $m->TglBuat ??= now();
            $m->DibuatOleh ??= Auth::id();
            if ($m->DitugaskanKepada) {
                $m->TglDitugaskan ??= now();
            }
        });
        static::created(function (self $m): void {
            DB::table('TTicketD')->insert(['Id' => (string) str()->orderedUuid(), 'IdTicket' => $m->Id, 'JenisAktivitas' => 'Pembuatan', 'IsiAktivitas' => $m->JudulTicket, 'TglAktivitas' => now(), 'TglBuat' => now(), 'DibuatOleh' => Auth::id()]);
            if ($m->DitugaskanKepada) {
                DB::table('TTicketDPenugasan')->insert(['Id' => (string) str()->orderedUuid(), 'IdTicket' => $m->Id, 'DitugaskanDari' => null, 'DitugaskanKepada' => $m->DitugaskanKepada, 'TglPenugasan' => now(), 'TglBuat' => now(), 'DibuatOleh' => Auth::id()]);
                self::notifyAssignee($m);
            }
        });
        static::updating(function (self $m): void {
            $m->TglEdit = now();
            $m->DieditOleh = Auth::id();
            if ($m->isDirty('DitugaskanKepada')) {
                $m->TglDitugaskan = $m->DitugaskanKepada ? now() : null;
            }
            if ($m->isDirty('IdStatusTicket')) {
                $final = DB::table('MStatusTicket')->where('Id', $m->IdStatusTicket)->value('StatusFinal');
                $m->TglDitutup = $final ? now() : null;
                $m->DitutupOleh = $final ? Auth::id() : null;
                $m->TglSelesai = $final ? ($m->TglSelesai ?: now()) : null;
            }
        });
        static::updated(function (self $m): void {
            if ($m->wasChanged('DitugaskanKepada') && $m->DitugaskanKepada) {
                DB::table('TTicketDPenugasan')->insert(['Id' => (string) str()->orderedUuid(), 'IdTicket' => $m->Id, 'DitugaskanDari' => $m->getOriginal('DitugaskanKepada'), 'DitugaskanKepada' => $m->DitugaskanKepada, 'TglPenugasan' => now(), 'TglBuat' => now(), 'DibuatOleh' => Auth::id()]);
                self::notifyAssignee($m);
            } if ($m->wasChanged('IdStatusTicket')) {
                DB::table('TTicketD')->insert(['Id' => (string) str()->orderedUuid(), 'IdTicket' => $m->Id, 'JenisAktivitas' => 'PerubahanStatus', 'StatusSebelum' => (string) $m->getOriginal('IdStatusTicket'), 'StatusSesudah' => (string) $m->IdStatusTicket, 'TglAktivitas' => now(), 'TglBuat' => now(), 'DibuatOleh' => Auth::id()]);
            }
        });
    }

    public static function nextNumber(): string
    {
        $date = now();
        $key = 'TCK-'.$date->format('Ymd');
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

        return TicketTaskSupport::number('TCK', $date, $sequence);
    }

    private static function notifyAssignee(self $ticket): void
    {
        $user = Pengguna::find($ticket->DitugaskanKepada);
        if ($user && in_array(AccessPermissions::TICKET_VIEW, $user->permissionCodes(), true)) {
            $user->notify(new TicketAssignedNotification($ticket));
        }
    }

    public function activities(): HasMany
    {
        return $this->hasMany(TicketActivity::class, 'IdTicket', 'Id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'IdTicket', 'Id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class, 'IdTicket', 'Id')->orderByDesc('TglPenugasan');
    }
}
