<?php

namespace App\Jobs;

use App\Services\Waha\WahaSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class RefreshWahaGroupMetadataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 20;

    public function __construct(public string $session, public string $groupJid)
    {
        $this->onQueue('waha-metadata');
    }

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(WahaSender $wahaSender): void
    {
        Log::info('WAHA group metadata fetch start.', [
            'session' => $this->session,
            'group_jid' => $this->groupJid,
        ]);

        $result = $wahaSender->getGroupMetadata($this->session, $this->groupJid);
        $subject = $this->extractSubject($result);

        if (! is_string($subject) || trim($subject) === '') {
            Log::warning('WAHA group metadata no subject.', [
                'session' => $this->session,
                'group_jid' => $this->groupJid,
                'ok' => $result['ok'] ?? false,
                'status' => $result['status'] ?? null,
                'error' => $result['error'] ?? null,
                'body_preview' => isset($result['body']) ? mb_substr((string) $result['body'], 0, 500) : null,
            ]);
            return;
        }

        $subject = trim($subject);

        if (str_ends_with($subject, '@g.us')) {
            Log::warning('WAHA group metadata subject is JID, ignore.', [
                'group_jid' => $this->groupJid,
                'subject' => $subject,
            ]);
            return;
        }

        $hasGroupName = Schema::hasColumn('TChat', 'GroupName');
        $sessionId = DB::table('MSesiWhatsapp')->where('KodeSesi', $this->session)->value('Id');

        $masterIds = [];
        if (Schema::hasColumn('MGrupWhatsapp', 'IdGrupWaha')) {
            try {
                $masterIds = DB::table('MGrupWhatsapp')->where('IdGrupWaha', $this->groupJid)->pluck('Id')->all();
            } catch (Throwable) {
                $masterIds = [];
            }
        }

        $rows = collect();
        $attempts = [];

        if ($sessionId) {
            $q = DB::table('TChat')
                ->where('JenisChat', 'Grup')
                ->where('IdSesiWhatsapp', $sessionId)
                ->where(function ($w) use ($masterIds): void {
                    $w->where('IdWahaTerdeteksi', $this->groupJid)
                        ->orWhere('NomorWhatsapp', $this->groupJid);
                    if ($masterIds !== []) {
                        $w->orWhereIn('IdGrupWhatsapp', $masterIds);
                    }
                });
            $rows = $q->select(['Id', 'NamaGrupWhatsapp', 'IdGrupWhatsapp', $hasGroupName ? 'GroupName' : DB::raw('NULL as GroupName')])->get();
            $attempts[] = 'by_session_id:'.$rows->count();
        }

        if ($rows->isEmpty()) {
            $q2 = DB::table('TChat as c')
                ->join('MSesiWhatsapp as s', 's.Id', '=', 'c.IdSesiWhatsapp')
                ->where('c.JenisChat', 'Grup')
                ->where('s.KodeSesi', $this->session)
                ->where(function ($w) use ($masterIds): void {
                    $w->where('c.IdWahaTerdeteksi', $this->groupJid)
                        ->orWhere('c.NomorWhatsapp', $this->groupJid);
                    if ($masterIds !== []) {
                        $w->orWhereIn('c.IdGrupWhatsapp', $masterIds);
                    }
                });
            $rows = $q2->select(['c.Id', 'c.NamaGrupWhatsapp', 'c.IdGrupWhatsapp', $hasGroupName ? 'c.GroupName' : DB::raw('NULL as GroupName')])->get();
            $attempts[] = 'by_session_code:'.$rows->count();
        }

        if ($rows->isEmpty()) {
            $q3 = DB::table('TChat')
                ->where('JenisChat', 'Grup')
                ->where(function ($w) use ($masterIds): void {
                    $w->where('IdWahaTerdeteksi', $this->groupJid)
                        ->orWhere('NomorWhatsapp', $this->groupJid);
                    if ($masterIds !== []) {
                        $w->orWhereIn('IdGrupWhatsapp', $masterIds);
                    }
                });
            $rows = $q3->select(['Id', 'NamaGrupWhatsapp', 'IdGrupWhatsapp', $hasGroupName ? 'GroupName' : DB::raw('NULL as GroupName')])->get();
            $attempts[] = 'without_session:'.$rows->count();
        }

        if ($rows->isEmpty()) {
            Log::warning('WAHA group metadata no TChat matched.', [
                'session' => $this->session,
                'group_jid' => $this->groupJid,
                'session_id' => $sessionId,
                'attempts' => $attempts,
                'master_ids' => $masterIds,
            ]);
            return;
        }

        $toUpdate = $rows->filter(function ($r) use ($hasGroupName, $subject): bool {
            $legacy = trim((string) ($r->NamaGrupWhatsapp ?? ''));
            if ($legacy !== $subject) {
                return true;
            }
            if ($hasGroupName) {
                $current = trim((string) ($r->GroupName ?? ''));
                if ($current !== $subject) {
                    return true;
                }
            }
            return false;
        })->pluck('Id');

        if ($toUpdate->isEmpty()) {
            Log::info('WAHA group metadata already up-to-date.', [
                'session' => $this->session,
                'group_jid' => $this->groupJid,
                'subject' => $subject,
                'matched' => $rows->count(),
                'attempts' => $attempts,
            ]);
            return;
        }

        $update = ['TglEdit' => now(), 'NamaGrupWhatsapp' => $subject];
        if ($hasGroupName) {
            $update['GroupName'] = $subject;
        }
        DB::table('TChat')->whereIn('Id', $toUpdate)->update($update);

        if ($masterIds !== [] && Schema::hasColumn('MGrupWhatsapp', 'NamaGrup')) {
            try {
                $mu = ['NamaGrup' => $subject];
                if (Schema::hasColumn('MGrupWhatsapp', 'TglEdit')) {
                    $mu['TglEdit'] = now();
                }
                DB::table('MGrupWhatsapp')->whereIn('Id', $masterIds)->update($mu);
            } catch (Throwable $e) {
                Log::warning('WAHA master update failed.', ['group_jid' => $this->groupJid, 'err' => $e->getMessage()]);
            }
        }

        Log::info('WAHA group metadata updated success.', [
            'session' => $this->session,
            'group_jid' => $this->groupJid,
            'subject' => $subject,
            'updated' => $toUpdate->count(),
            'matched' => $rows->count(),
            'attempts' => $attempts,
        ]);

        SendBroadcastDebouncedJob::dispatchDebounced((string) $toUpdate->first());
    }

    private function extractSubject(array $result): ?string
    {
        $body = $result['body'] ?? null;
        $candidates = [];

        if (is_string($body) && trim($body) !== '') {
            $payload = json_decode($body, true);
            if (is_array($payload)) {
                $candidates = [
                    Arr::get($payload, 'subject'),
                    Arr::get($payload, 'name'),
                    Arr::get($payload, 'title'),
                    Arr::get($payload, 'groupMetadata.subject'),
                    Arr::get($payload, 'groupMetadata.name'),
                    Arr::get($payload, 'groupMetadata.title'),
                    Arr::get($payload, 'info.subject'),
                    Arr::get($payload, 'chat.name'),
                    Arr::get($payload, 'group.name'),
                    Arr::get($payload, 'data.subject'),
                ];
            }
        }

        $candidates[] = $result['subject'] ?? null;

        foreach ($candidates as $c) {
            if (is_string($c) && trim($c) !== '') {
                return trim($c);
            }
        }

        return null;
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('WAHA group metadata job failed.', [
            'group_jid' => $this->groupJid,
            'exception' => $exception::class,
        ]);
    }
}