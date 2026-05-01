<?php

namespace App\Services\Waha;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class WahaWebhookProcessor
{
    public function __construct(private readonly WahaSender $wahaSender)
    {
    }

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
                $parsed = $this->resolveLidPhoneNumber((string) $session->KodeSesi, $parsed);

                if ($parsed['is_status_broadcast']) {
                    DB::table('TLogWebhookWaha')->where('Id', $webhookId)->update([
                        'SudahDiproses' => true,
                        'TglDiproses' => now(),
                        'TglEdit' => now(),
                    ]);

                    return [
                        'ok' => true,
                        'ignored' => true,
                        'webhook_id' => $webhookId,
                        'jenis_chat' => 'Status',
                        'message' => 'WhatsApp status broadcast ignored.',
                    ];
                }

                $duplicate = $this->duplicateMessage($parsed['id_pesan']);

                if ($duplicate) {
                    DB::table('TLogWebhookWaha')->where('Id', $webhookId)->update([
                        'SudahDiproses' => true,
                        'TglDiproses' => now(),
                        'TglEdit' => now(),
                    ]);

                    return [
                        'ok' => true,
                        'duplicate' => true,
                        'chat_id' => $duplicate->IdChatM,
                        'webhook_id' => $webhookId,
                        'jenis_chat' => $parsed['jenis_chat'],
                        'message' => 'Duplicate WAHA message event ignored.',
                    ];
                }

                $mapping = $this->resolveCustomerMapping($parsed);
                $chatId = $this->findOrCreateChat($session->Id, $parsed, $mapping);

                $chatMessage = [
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
                ];

                if (Schema::hasColumn('TChatD', 'NamaFileMedia')) {
                    $chatMessage['NamaFileMedia'] = $parsed['nama_file_media'];
                }

                if (Schema::hasColumn('TChatD', 'TipeMime')) {
                    $chatMessage['TipeMime'] = $parsed['tipe_mime'];
                }

                DB::table('TChatD')->insert($chatMessage);

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

    private function duplicateMessage(?string $messageId): ?object
    {
        if (! $messageId) {
            return null;
        }

        return DB::table('TChatD')
            ->where('IdPesanWaha', $messageId)
            ->select('Id', 'IdChatM')
            ->first();
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
            ?? Arr::get($message, 'id.remote')
            ?? Arr::get($message, 'id._serialized')
            ?? Arr::get($message, '_data.id._serialized')
            ?? Arr::get($message, '_data.id.remote')
            ?? Arr::get($message, '_data.Info.Chat')
            ?? Arr::get($message, '_data.chatId')
            ?? Arr::get($message, 'key.remoteJid')
            ?? Arr::get($message, 'chat.id')
            ?? Arr::get($message, 'chat.id._serialized')
            ?? Arr::get($message, 'to')
            ?? Arr::get($message, 'to.id')
            ?? Arr::get($message, 'groupId')
            ?? Arr::get($message, 'group.id')
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
        $mimeType = $this->mediaMimeType($message);
        $mediaUrl = $this->mediaUrl($message);
        $messageId = $this->messageId($message);

        return [
            'id_pesan' => $messageId,
            'jenis_chat' => $isGroup ? 'Grup' : 'Pribadi',
            'group_jid' => $isGroup ? $remoteId : null,
            'pengirim_jid' => $senderJid,
            'pengirim_nomor' => $this->normalisasiNomorWhatsapp($senderJid),
            'pengirim_nama' => $this->stringValue(Arr::get($message, 'sender.pushname') ?? Arr::get($message, 'notifyName') ?? Arr::get($message, 'pushName') ?? ''),
            'isi_pesan' => $this->messageBody($message),
            'jenis_pesan' => $this->messageType($message, $mimeType, $mediaUrl),
            'url_media' => $mediaUrl,
            'nama_file_media' => $this->mediaFileName($message, $mediaUrl),
            'tipe_mime' => $mimeType,
            'from_me' => $fromMe,
            'tgl_pesan' => $this->messageDate($message),
            'is_status_broadcast' => $this->isStatusBroadcast($payload, $message, $remoteId, $messageId),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $message
     */
    private function isStatusBroadcast(array $payload, array $message, string $remoteId, ?string $messageId): bool
    {
        $candidates = [
            $remoteId,
            $messageId,
            Arr::get($message, 'chatId'),
            Arr::get($message, 'from'),
            Arr::get($message, 'from.id'),
            Arr::get($message, 'id.remote'),
            Arr::get($message, 'id._serialized'),
            Arr::get($message, '_data.id._serialized'),
            Arr::get($message, '_data.id.remote'),
            Arr::get($message, '_data.Info.Chat'),
            Arr::get($message, '_data.chatId'),
            Arr::get($message, 'key.remoteJid'),
            Arr::get($message, 'chat.id'),
            Arr::get($message, 'chat.id._serialized'),
            Arr::get($message, 'to'),
            Arr::get($message, 'to.id'),
            Arr::get($payload, 'chatId'),
        ];

        foreach ($candidates as $candidate) {
            if (! is_scalar($candidate)) {
                continue;
            }

            $value = (string) $candidate;

            if ($value === 'status@broadcast' || str_contains($value, '_status@broadcast_')) {
                return true;
            }
        }

        return false;
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
    private function messageType(array $message, ?string $mimeType, ?string $mediaUrl): string
    {
        $rawType = strtolower($this->stringValue(
            Arr::get($message, 'type')
            ?? Arr::get($message, 'message.type')
            ?? Arr::get($message, '_data.type')
            ?? ''
        ));
        $mime = strtolower((string) $mimeType);
        $hasMedia = (bool) (Arr::get($message, 'hasMedia') ?? Arr::get($message, '_data.hasMedia') ?? false);

        if (str_starts_with($mime, 'image/') || str_starts_with($rawType, 'image/') || in_array($rawType, ['image', 'gambar', 'photo', 'picture'], true)) {
            return 'Gambar';
        }

        if (str_starts_with($mime, 'video/') || str_starts_with($rawType, 'video/') || $rawType === 'video') {
            return 'Video';
        }

        if (str_starts_with($mime, 'audio/') || str_starts_with($rawType, 'audio/') || in_array($rawType, ['audio', 'ptt', 'voice'], true)) {
            return 'Audio';
        }

        if ($rawType === 'sticker') {
            return 'Stiker';
        }

        if ($mime !== '' || $mediaUrl || $hasMedia || in_array($rawType, ['document', 'file'], true)) {
            return 'Dokumen';
        }

        return 'Teks';
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function mediaMimeType(array $message): ?string
    {
        foreach ([
            'media.mimetype',
            'media.mimeType',
            'mimetype',
            'mimeType',
            'file.mimetype',
            'file.mimeType',
            '_data.mimetype',
        ] as $key) {
            $value = Arr::get($message, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function mediaUrl(array $message): ?string
    {
        foreach ([
            'media.url',
            'media.downloadUrl',
            'media.file.url',
            'mediaUrl',
            'downloadUrl',
            'file.url',
            'url',
            '_data.deprecatedMms3Url',
        ] as $key) {
            $value = Arr::get($message, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function mediaFileName(array $message, ?string $mediaUrl): ?string
    {
        foreach ([
            'media.filename',
            'media.fileName',
            'filename',
            'fileName',
            'file.name',
            'document.filename',
            '_data.filename',
        ] as $key) {
            $value = Arr::get($message, $key);

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        if (! $mediaUrl) {
            return null;
        }

        $path = parse_url($mediaUrl, PHP_URL_PATH);

        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $fileName = basename($path);

        return $fileName !== '' ? $fileName : null;
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
                $parsed['pengirim_nomor'] ? $parsed['pengirim_nomor'].'@c.us' : null,
                $parsed['pengirim_nomor'] ? $parsed['pengirim_nomor'].'@lid' : null,
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
            $query->where(function ($query) use ($parsed): void {
                $query->where('NomorWhatsapp', $parsed['pengirim_nomor'] ?: '-');

                if (Schema::hasColumn('TChatM', 'IdWahaTerdeteksi') && ($parsed['pengirim_jid'] ?? null)) {
                    $query->orWhere('IdWahaTerdeteksi', $parsed['pengirim_jid']);
                }
            });
        }

        $chat = $query->orderByDesc('TglChatTerakhir')->first();
        $statusDitutupId = DB::table('MStatusChat')->where('KodeStatusChat', 'DITUTUP')->value('Id');

        if ($chat && strtoupper((string) $chat->IdStatusChat) !== strtoupper((string) $statusDitutupId)) {
            $update = [
                'IdCustomer' => $mapping['IdCustomer'],
                'IdInstansi' => $mapping['IdInstansi'],
                'IdNomorWhatsapp' => $mapping['IdNomorWhatsapp'],
                'IdGrupWhatsapp' => $mapping['IdGrupWhatsapp'],
                'NamaKontak' => $mapping['NamaKontak'],
                'NamaGrupWhatsapp' => $mapping['NamaGrupWhatsapp'],
                'TglEdit' => now(),
            ];

            if (Schema::hasColumn('TChatM', 'IdWahaTerdeteksi')) {
                $update['IdWahaTerdeteksi'] = $parsed['pengirim_jid'] ?: $parsed['group_jid'];
            }

            if (Schema::hasColumn('TChatM', 'NomorWhatsappTerdeteksi') && $parsed['pengirim_nomor']) {
                $update['NomorWhatsappTerdeteksi'] = $parsed['pengirim_nomor'];
            }

            if ($parsed['jenis_chat'] !== 'Grup' && $parsed['pengirim_nomor']) {
                $update['NomorWhatsapp'] = $parsed['pengirim_nomor'];
            }

            DB::table('TChatM')->where('Id', $chat->Id)->update($update);

            return $chat->Id;
        }

        $id = (string) Str::orderedUuid();
        $chat = [
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
        ];

        if (Schema::hasColumn('TChatM', 'IdWahaTerdeteksi')) {
            $chat['IdWahaTerdeteksi'] = $parsed['pengirim_jid'] ?: $parsed['group_jid'];
        }

        if (Schema::hasColumn('TChatM', 'NomorWhatsappTerdeteksi') && $parsed['pengirim_nomor']) {
            $chat['NomorWhatsappTerdeteksi'] = $parsed['pengirim_nomor'];
        }

        DB::table('TChatM')->insert($chat);

        return $id;
    }

    private function normalisasiNomorWhatsapp(?string $nomor): ?string
    {
        if (! $nomor) {
            return null;
        }

        if (str_contains($nomor, '@lid')) {
            return null;
        }

        $nomor = preg_replace('/@.+$/', '', $nomor) ?: $nomor;
        $nomor = preg_replace('/:.+$/', '', $nomor) ?: $nomor;

        return preg_replace('/[^0-9]/', '', $nomor) ?: null;
    }

    /**
     * @param  array<string, mixed>  $parsed
     * @return array<string, mixed>
     */
    private function resolveLidPhoneNumber(string $session, array $parsed): array
    {
        $jid = (string) ($parsed['pengirim_jid'] ?? '');

        if (! str_contains($jid, '@lid')) {
            return $parsed;
        }

        $result = $this->wahaSender->getPhoneNumberByLid($session ?: 'default', $jid);
        $phone = $result['phone'] ?? null;

        if (is_string($phone) && $phone !== '') {
            $parsed['pengirim_nomor'] = $phone;
            $parsed['pengirim_phone_jid'] = ($result['pn'] ?? null) ?: $phone . '@c.us';
        }

        return $parsed;
    }

    private function stringValue(mixed $value): string
    {
        if (is_scalar($value) || $value === null) {
            return trim((string) $value);
        }

        return trim(json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '');
    }
}
