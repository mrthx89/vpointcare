<?php

namespace App\Services\Waha;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class WahaWebhookProcessor
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function process(array $payload): array
    {
        return DB::transaction(function () use ($payload): array {
            $message = $this->messagePayload($payload);
            $session = $this->findOrCreateSession($this->sessionCode($payload, $message));

            $webhookId = (string) Str::orderedUuid();
            DB::table('TLogWebhookWaha')->insert([
                'Id' => $webhookId,
                'IdSesiWhatsapp' => $session->Id,
                'JenisEvent' => $this->stringValue(Arr::get($payload, 'event') ?? Arr::get($payload, 'type') ?? 'message'),
                'PayloadJson' => json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                'TglDiterima' => now(),
                'SudahDiproses' => false,
                'TglBuat' => now(),
            ]);

            try {
                $parsed = $this->parseMessage($payload, $message);
                $mapping = $this->resolveCustomerMapping($parsed);
                $chatId = $this->findOrCreateChat($session->Id, $parsed, $mapping);

                DB::table('TChatD')->insert([
                    'Id' => (string) Str::orderedUuid(),
                    'IdChatM' => $chatId,
                    'IdLogWebhookWaha' => $webhookId,
                    'IdPesanWaha' => $parsed['id_pesan'],
                    'ArahPesan' => $parsed['from_me'] ? 'Keluar' : 'Masuk',
                    'JenisPesan' => $parsed['jenis_pesan'],
                    'IsiPesan' => $parsed['isi_pesan'],
                    'UrlMedia' => $parsed['url_media'],
                    'PayloadJson' => json_encode($message, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
                    'PengirimNomorWhatsapp' => $parsed['pengirim_nomor'],
                    'PengirimNamaKontak' => $parsed['pengirim_nama'],
                    'DikirimOlehCustomer' => ! $parsed['from_me'],
                    'TglPesan' => $parsed['tgl_pesan'],
                    'StatusKirim' => $parsed['from_me'] ? 'Terkirim' : null,
                    'TglBuat' => now(),
                ]);

                DB::table('TChatM')->where('Id', $chatId)->update([
                    'TglChatTerakhir' => $parsed['tgl_pesan'],
                    'JumlahPesanBelumDibaca' => DB::raw($parsed['from_me'] ? 'JumlahPesanBelumDibaca' : 'JumlahPesanBelumDibaca + 1'),
                    'TglEdit' => now(),
                ]);

                DB::table('TLogWebhookWaha')->where('Id', $webhookId)->update([
                    'SudahDiproses' => true,
                    'TglDiproses' => now(),
                    'TglEdit' => now(),
                ]);

                return [
                    'ok' => true,
                    'chat_id' => $chatId,
                    'webhook_id' => $webhookId,
                    'jenis_chat' => $parsed['jenis_chat'],
                    'id_instansi' => $mapping['IdInstansi'] ?? null,
                ];
            } catch (Throwable $exception) {
                DB::table('TLogWebhookWaha')->where('Id', $webhookId)->update([
                    'PesanError' => $exception->getMessage(),
                    'TglEdit' => now(),
                ]);

                throw $exception;
            }
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function messagePayload(array $payload): array
    {
        $message = Arr::get($payload, 'payload')
            ?? Arr::get($payload, 'data')
            ?? Arr::get($payload, 'message')
            ?? $payload;

        return is_array($message) ? $message : $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $message
     */
    private function sessionCode(array $payload, array $message): string
    {
        return $this->stringValue(
            Arr::get($payload, 'session')
            ?? Arr::get($payload, 'sessionId')
            ?? Arr::get($message, 'session')
            ?? Arr::get($message, 'sessionId')
            ?? 'default'
        );
    }

    private function findOrCreateSession(string $kodeSesi): object
    {
        $session = DB::table('MSesiWhatsapp')->where('KodeSesi', $kodeSesi)->first();

        if ($session) {
            return $session;
        }

        $id = (string) Str::orderedUuid();
        DB::table('MSesiWhatsapp')->insert([
            'Id' => $id,
            'KodeSesi' => $kodeSesi,
            'NamaSesi' => $kodeSesi,
            'BaseUrlWaha' => config('services.waha.base_url', '-'),
            'StatusSesi' => 'Aktif',
            'NonAktif' => false,
            'TglBuat' => now(),
        ]);

        return DB::table('MSesiWhatsapp')->where('Id', $id)->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    private function parseMessage(array $payload, array $message): array
    {
        $remoteId = $this->stringValue(
            Arr::get($message, 'chatId')
            ?? Arr::get($message, 'from')
            ?? Arr::get($message, 'from.id')
            ?? Arr::get($message, '_data.id.remote')
            ?? Arr::get($payload, 'chatId')
            ?? ''
        );

        $fromMe = (bool) (Arr::get($message, 'fromMe') ?? Arr::get($message, '_data.id.fromMe') ?? false);
        $isGroup = str_contains($remoteId, '@g.us') || (bool) (Arr::get($message, 'isGroup') ?? false);
        $participant = $this->stringValue(
            Arr::get($message, 'participant')
            ?? Arr::get($message, 'author')
            ?? Arr::get($message, 'sender.id')
            ?? Arr::get($message, '_data.author')
            ?? ($isGroup ? '' : $remoteId)
        );
        $senderJid = $isGroup ? $participant : $remoteId;

        return [
            'id_pesan' => $this->messageId($message),
            'jenis_chat' => $isGroup ? 'Grup' : 'Pribadi',
            'group_jid' => $isGroup ? $remoteId : null,
            'pengirim_jid' => $senderJid,
            'pengirim_nomor' => $this->normalisasiNomorWhatsapp($senderJid),
            'pengirim_nama' => $this->stringValue(Arr::get($message, 'sender.pushname') ?? Arr::get($message, 'notifyName') ?? Arr::get($message, 'pushName') ?? ''),
            'isi_pesan' => $this->messageBody($message),
            'jenis_pesan' => $this->stringValue(Arr::get($message, 'type') ?? Arr::get($message, 'media.mimetype') ?? 'Teks'),
            'url_media' => Arr::get($message, 'media.url') ?? Arr::get($message, 'downloadUrl') ?? null,
            'from_me' => $fromMe,
            'tgl_pesan' => $this->messageDate($message),
        ];
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function messageId(array $message): ?string
    {
        $id = Arr::get($message, 'id._serialized') ?? Arr::get($message, 'id') ?? Arr::get($message, '_data.id.id');

        if (is_array($id)) {
            return Arr::get($id, '_serialized') ?? json_encode($id);
        }

        return $id ? $this->stringValue($id) : null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function messageBody(array $message): ?string
    {
        $body = Arr::get($message, 'body')
            ?? Arr::get($message, 'text')
            ?? Arr::get($message, 'caption')
            ?? Arr::get($message, 'message.conversation')
            ?? Arr::get($message, '_data.body');

        return $body === null ? null : $this->stringValue($body);
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function messageDate(array $message): \DateTimeInterface
    {
        $timestamp = Arr::get($message, 'timestamp') ?? Arr::get($message, 't') ?? null;

        if (is_numeric($timestamp)) {
            return now()->setTimestamp((int) $timestamp);
        }

        return now();
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function resolveCustomerMapping(array $parsed): array
    {
        $nomor = null;
        $grup = null;

        if ($parsed['pengirim_nomor']) {
            $nomor = DB::table('MNomorWhatsapp')->where('NomorWhatsapp', $parsed['pengirim_nomor'])->where('NonAktif', false)->first();
        }

        if (! $nomor && Schema::hasColumn('MNomorWhatsapp', 'IdWaha')) {
            $wahaIds = array_values(array_filter(array_unique([
                $parsed['pengirim_jid'] ?? null,
                $parsed['pengirim_nomor'] ?? null,
                $parsed['pengirim_nomor'] ? $parsed['pengirim_nomor'] . '@c.us' : null,
                $parsed['pengirim_nomor'] ? $parsed['pengirim_nomor'] . '@lid' : null,
            ])));

            if ($wahaIds !== []) {
                $nomor = DB::table('MNomorWhatsapp')
                    ->whereIn('IdWaha', $wahaIds)
                    ->where('NonAktif', false)
                    ->first();
            }
        }

        if ($parsed['group_jid']) {
            $grup = DB::table('MGrupWhatsapp')->where('IdGrupWaha', $parsed['group_jid'])->where('NonAktif', false)->first();
        }

        return [
            'IdCustomer' => $nomor->IdCustomer ?? null,
            'IdInstansi' => $grup->IdInstansi ?? $nomor->IdInstansi ?? null,
            'IdNomorWhatsapp' => $nomor->Id ?? null,
            'IdGrupWhatsapp' => $grup->Id ?? null,
            'NamaKontak' => $nomor->NamaKontak ?? $parsed['pengirim_nama'] ?? null,
            'NamaGrupWhatsapp' => $grup->NamaGrup ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @param  array<string, mixed>  $mapping
     */
    private function findOrCreateChat(string $sessionId, array $parsed, array $mapping): string
    {
        $query = DB::table('TChatM')->where('IdSesiWhatsapp', $sessionId)->where('JenisChat', $parsed['jenis_chat']);

        if ($parsed['jenis_chat'] === 'Grup' && $mapping['IdGrupWhatsapp']) {
            $query->where('IdGrupWhatsapp', $mapping['IdGrupWhatsapp']);
        } else {
            $query->where('NomorWhatsapp', $parsed['pengirim_nomor'] ?: '-');
        }

        $chat = $query->orderByDesc('TglChatTerakhir')->first();

        if ($chat) {
            DB::table('TChatM')->where('Id', $chat->Id)->update([
                'IdCustomer' => $mapping['IdCustomer'],
                'IdInstansi' => $mapping['IdInstansi'],
                'IdNomorWhatsapp' => $mapping['IdNomorWhatsapp'],
                'IdGrupWhatsapp' => $mapping['IdGrupWhatsapp'],
                'NamaKontak' => $mapping['NamaKontak'],
                'NamaGrupWhatsapp' => $mapping['NamaGrupWhatsapp'],
                'TglEdit' => now(),
            ]);

            return $chat->Id;
        }

        $id = (string) Str::orderedUuid();
        DB::table('TChatM')->insert([
            'Id' => $id,
            'IdSesiWhatsapp' => $sessionId,
            'IdStatusChat' => DB::table('MStatusChat')->where('KodeStatusChat', 'MENUNGGU_CS')->value('Id'),
            'IdCustomer' => $mapping['IdCustomer'],
            'IdInstansi' => $mapping['IdInstansi'],
            'IdNomorWhatsapp' => $mapping['IdNomorWhatsapp'],
            'IdGrupWhatsapp' => $mapping['IdGrupWhatsapp'],
            'JenisChat' => $parsed['jenis_chat'],
            'NomorWhatsapp' => $parsed['pengirim_nomor'] ?: '-',
            'NamaKontak' => $mapping['NamaKontak'],
            'NamaGrupWhatsapp' => $mapping['NamaGrupWhatsapp'],
            'Prioritas' => 'Normal',
            'TglChatTerakhir' => $parsed['tgl_pesan'],
            'JumlahPesanBelumDibaca' => 0,
            'TglBuat' => now(),
        ]);

        return $id;
    }

    private function normalisasiNomorWhatsapp(?string $nomor): ?string
    {
        if (! $nomor) {
            return null;
        }

        $nomor = preg_replace('/@.+$/', '', $nomor) ?: $nomor;
        $nomor = preg_replace('/:.+$/', '', $nomor) ?: $nomor;

        return preg_replace('/[^0-9]/', '', $nomor) ?: null;
    }

    private function stringValue(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return trim((string) $value);
        }

        return trim(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }
}
