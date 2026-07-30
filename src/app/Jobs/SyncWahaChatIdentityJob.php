<?php

namespace App\Jobs;

use App\Services\Waha\WahaSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class SyncWahaChatIdentityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public string $chatId)
    {
        $this->onQueue('webhooks');
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [30, 120];
    }

    public static function dispatchDebounced(string $chatId): void
    {
        if (! Cache::add(self::cacheKey($chatId), true, now()->addSeconds(60))) {
            return;
        }

        self::dispatch($chatId);
    }

    public function handle(WahaSender $wahaSender): void
    {
        $chat = DB::table('TChat')->where('Id', $this->chatId)->first();

        if (! $chat) {
            return;
        }

        $session = DB::table('MSesiWhatsapp')->where('Id', $chat->IdSesiWhatsapp)->first();

        if (! $session || ! is_string($session->KodeSesi) || $session->KodeSesi === '') {
            $this->markFailed();

            throw new RuntimeException('WAHA identity metadata synchronization failed.');
        }

        $isGroup = $chat->JenisChat === 'Grup';
        $identityId = $isGroup
            ? $this->groupIdentityId($chat)
            : $this->personalIdentityId($chat);

        if ($identityId === null) {
            $this->markFailed();

            throw new RuntimeException('WAHA identity metadata synchronization failed.');
        }

        $metadata = $isGroup
            ? $wahaSender->getGroupInfo($session->KodeSesi, $identityId)
            : $wahaSender->getContactInfo($session->KodeSesi, $identityId);

        if (! ($metadata['ok'] ?? false)) {
            $this->markFailed();

            throw new RuntimeException('WAHA identity metadata synchronization failed.');
        }

        $profile = $wahaSender->getContactProfilePictureUrl(
            $session->KodeSesi,
            (string) ($metadata['id'] ?? $identityId)
        );

        $updates = [
            'IdWahaTerdeteksi' => (string) ($metadata['id'] ?? $identityId),
            'StatusIdentitasWaha' => 'success',
            'TglIdentitasWahaDiambil' => now(),
            'PesanErrorIdentitasWaha' => null,
            'TglEdit' => now(),
        ];

        if ($isGroup) {
            $updates['NamaGrupWaha'] = $metadata['name'];
        } else {
            $name = $metadata['name'] ?? $metadata['pushname'];

            if (is_string($name) && $name !== '') {
                $updates['NamaKontakWaha'] = $name;
            }

            if (! empty($metadata['phone'])) {
                $updates['NomorWhatsappTerdeteksi'] = $metadata['phone'];
            }
        }

        if (($profile['ok'] ?? false) && ! empty($profile['url'])) {
            $updates['UrlFotoProfil'] = $profile['url'];
            $updates['TglFotoProfilDiambil'] = now();
        }

        $snapshotChanged = $this->snapshotChanged($chat, $updates);

        DB::transaction(function () use ($updates): void {
            DB::table('TChat')->where('Id', $this->chatId)->update($updates);
        });

        $participantSnapshotChanged = $isGroup
            ? $this->syncParticipantProfiles($wahaSender, $session->KodeSesi)
            : false;

        if ($snapshotChanged || $participantSnapshotChanged) {
            SendBroadcastDebouncedJob::dispatchDebounced($this->chatId);
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->markFailed();
    }

    private function markFailed(): void
    {
        DB::table('TChat')->where('Id', $this->chatId)->update([
            'StatusIdentitasWaha' => 'failed',
            'TglIdentitasWahaDiambil' => now(),
            'PesanErrorIdentitasWaha' => 'WAHA identity metadata synchronization failed.',
            'TglEdit' => now(),
        ]);
    }

    private function groupIdentityId(object $chat): ?string
    {
        foreach ([$chat->NomorWhatsapp, $chat->IdWahaTerdeteksi, $this->rawGroupIdFromPayload()] as $candidate) {
            if ($this->isGroupJid($candidate)) {
                return trim((string) $candidate);
            }
        }

        $mappedGroupId = $chat->IdGrupWhatsapp
            ? DB::table('MGrupWhatsapp')->where('Id', $chat->IdGrupWhatsapp)->value('IdGrupWaha')
            : null;

        return $this->isGroupJid($mappedGroupId) ? trim((string) $mappedGroupId) : null;
    }

    private function personalIdentityId(object $chat): ?string
    {
        foreach ([$chat->IdWahaTerdeteksi, $chat->NomorWhatsappTerdeteksi, $chat->NomorWhatsapp] as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    private function rawGroupIdFromPayload(): ?string
    {
        $payload = DB::table('TChatD')
            ->where('IdChat', $this->chatId)
            ->orderByDesc('TglPesan')
            ->value('PayloadJson');
        $decoded = is_string($payload) ? json_decode($payload, true) : null;

        if (! is_array($decoded)) {
            return null;
        }

        foreach (['chatId', 'from', 'to', 'id.remote', 'id._serialized', 'key.remoteJid', 'chat.id', 'chat.id._serialized'] as $path) {
            $candidate = Arr::get($decoded, $path);

            if ($this->isGroupJid($candidate)) {
                return trim((string) $candidate);
            }
        }

        return null;
    }

    private function isGroupJid(mixed $value): bool
    {
        return is_string($value) && str_ends_with(strtolower(trim($value)), '@g.us');
    }

    private function syncParticipantProfiles(WahaSender $wahaSender, string $session): bool
    {
        $participants = DB::table('TChatD')
            ->where('IdChat', $this->chatId)
            ->where('ArahPesan', 'Masuk')
            ->where('DikirimOlehCustomer', true)
            ->whereNotNull('PengirimIdWaha')
            ->where(function ($query): void {
                $query->where('PengirimIdWaha', 'like', '%@c.us')
                    ->orWhere('PengirimIdWaha', 'like', '%@s.whatsapp.net')
                    ->orWhere('PengirimIdWaha', 'like', '%@lid');
            })
            ->where(function ($query): void {
                $query->whereNull('UrlFotoProfilPengirim')
                    ->orWhere('UrlFotoProfilPengirim', '')
                    ->orWhereNull('TglFotoProfilPengirimDiambil')
                    ->orWhere('TglFotoProfilPengirimDiambil', '<', now()->subDay());
            })
            ->select('PengirimIdWaha')
            ->selectRaw('MAX(TglPesan) as TglPesanTerakhir')
            ->groupBy('PengirimIdWaha')
            ->orderByDesc('TglPesanTerakhir')
            ->limit(20)
            ->pluck('PengirimIdWaha');

        $snapshotChanged = false;

        foreach ($participants as $participant) {
            $participantJid = is_string($participant) ? trim($participant) : '';

            if (! $this->isParticipantJid($participantJid)
                || ! Cache::add(self::participantProfileCacheKey($session, $participantJid), true, now()->addHour())) {
                continue;
            }

            $profile = $wahaSender->getContactProfilePictureUrl($session, $participantJid);
            $url = ($profile['ok'] ?? false) && is_string($profile['url'] ?? null)
                ? trim($profile['url'])
                : '';

            if ($url === '') {
                continue;
            }

            $updated = DB::table('TChatD')
                ->where('IdChat', $this->chatId)
                ->where('PengirimIdWaha', $participantJid)
                ->update([
                    'UrlFotoProfilPengirim' => $url,
                    'TglFotoProfilPengirimDiambil' => now(),
                ]);

            $snapshotChanged = $updated > 0 || $snapshotChanged;
        }

        return $snapshotChanged;
    }

    private function isParticipantJid(mixed $value): bool
    {
        return is_string($value)
            && preg_match('/^[^@\s]+@(c\.us|s\.whatsapp\.net|lid)$/i', trim($value)) === 1;
    }

    /** @param array<string, mixed> $updates */
    private function snapshotChanged(object $chat, array $updates): bool
    {
        foreach (['IdWahaTerdeteksi', 'NamaKontakWaha', 'NamaGrupWaha', 'NomorWhatsappTerdeteksi', 'UrlFotoProfil'] as $column) {
            if (array_key_exists($column, $updates) && $chat->{$column} !== $updates[$column]) {
                return true;
            }
        }

        return false;
    }

    private static function cacheKey(string $chatId): string
    {
        return 'waha:identity-sync:'.$chatId;
    }

    private static function participantProfileCacheKey(string $session, string $participantJid): string
    {
        return 'waha:participant-profile:'.$session.':'.sha1($participantJid);
    }
}
